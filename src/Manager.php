<?php
namespace JakubOnderka\PhpParallelLint;

/*
Copyright (c) 2012, Jakub Onderka
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those
of the authors and should not be interpreted as representing official policies,
either expressed or implied, of the FreeBSD Project.
 */


require_once __DIR__ . '/exceptions.php';
require_once __DIR__ . '/Settings.php';
require_once __DIR__ . '/Process.php';
require_once __DIR__ . '/Output.php';
require_once __DIR__ . '/Error.php';

class Manager
{
    const CODE_OK = 0,
        CODE_ERROR = 255;

    /** @var Output */
    protected $output;

    /**
     * @param array $arguments
     * @return Settings
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function parseArguments(array $arguments)
    {
        $arguments = new ArrayIterator(array_slice($arguments, 1));
        $setting = new Settings;

        foreach ($arguments as $argument) {
            if ($argument{0} !== '-') {
                $setting->paths[] = $argument;
            } else {
                switch ($argument) {
                    case '-p':
                        $setting->phpExecutable = $arguments->getNext();
                        break;

                    case '-s':
                    case '--short':
                        $setting->shortTag = true;
                        break;

                    case '-a':
                    case '--asp':
                        $setting->aspTags = true;
                        break;

                    case '--exclude':
                        $setting->excluded[] = $arguments->getNext();
                        break;

                    case '-e':
                        $setting->extensions = array_map('trim', explode(',', $arguments->getNext()));
                        break;

                    case '-j':
                        $setting->parallelJobs = max((int) $arguments->getNext(), 1);
                        break;

                    default:
                        throw new InvalidArgumentException($argument);
                }
            }
        }

        if (empty($setting->paths)) {
            throw new Exception('No path set.');
        }

        return $setting;
    }

    /**
     * @param null|Settings $settings
     * @return bool
     * @throws \Exception
     */
    public function run(Settings $settings = null)
    {
        $settings = $settings ?: new Settings;
        $output = $this->output ?: new Output(new ConsoleWriter);

        $this->checkPhpExecutableExists($settings->phpExecutable);

        $cmdLine = $this->getCmdLine($settings);
        $files = $this->getFilesFromPaths($settings->paths, $settings->extensions, $settings->excluded);

        if (empty($files)) {
            throw new Exception('No file found to check.');
        }

        $output->setTotalFileCount(count($files));

        /** @var LintProcess[] $running */
        $running = $errors = array();
        $checkedFiles = $filesWithSyntaxError = 0;

        $startTime = microtime(true);

        while ($files || $running) {
            for ($i = count($running); $files && $i < $settings->parallelJobs; $i++) {
                $file = array_shift($files);
                $running[$file] = new LintProcess($cmdLine . escapeshellarg($file));
            }

            if (count($running) > 1) {
                usleep(20000);
            }

            foreach ($running as $file => $process) {
                if ($process->isFinished()) {
                    unset($running[$file]);

                    if ($process->isFail()) {
                        $output->fail();
                        $errors[] = new Error($file, $process->getErrorOutput());
                    } else {
                        $checkedFiles++;
                        if ($process->hasSyntaxError()) {
                            $errors[] = new SyntaxError($file, $process->getSyntaxError());
                            $filesWithSyntaxError++;
                            $output->error();
                        } else {
                            $output->ok();
                        }
                    }
                }
            }
        }

        $testTime = round(microtime(true) - $startTime, 1);

        $output->writeNewLine(2);

        $message = "Checked $checkedFiles files in $testTime second, ";
        if ($filesWithSyntaxError === 0) {
            $message .= "no syntax error found";
        } else {
            $message .= "syntax error found in $filesWithSyntaxError ";
            $message .= ($filesWithSyntaxError === 1 ? 'file' : 'files');
        }

        $output->writeLine($message);

        if (!empty($errors)) {
            $output->writeNewLine();

            foreach ($errors as $errorMessage) {
                $output->writeLine('----------------------------------------------------------------------');
                $output->writeLine($errorMessage);
            }

            return false;
        }

        return true;
    }

    /**
     * @param Output $output
     */
    public function setOutput(Output $output)
    {
        $this->output = $output;
    }

    /**
     * @param string $phpExecutable
     * @throws \Exception
     */
    protected function checkPhpExecutableExists($phpExecutable)
    {
        exec(escapeshellarg($phpExecutable) . ' -v', $nothing, $result);

        if ($result !== self::CODE_OK && $result !== self::CODE_ERROR) {
            throw new \Exception("Unable to execute '{$phpExecutable} -v'");
        }
    }

    /**
     * @param Settings $settings
     * @return string
     */
    protected function getCmdLine(Settings $settings)
    {
        $cmdLine = escapeshellarg($settings->phpExecutable);
        $cmdLine .= ' -d asp_tags=' . ($settings->aspTags ? 'On' : 'Off');
        $cmdLine .= ' -d short_open_tag=' . ($settings->shortTag ? 'On' : 'Off');
        return $cmdLine . ' -n -l ';
    }

    /**
     * @param array $paths
     * @param array $extensions
     * @param array $excluded
     * @return array
     * @throws NotExistsPathException
     */
    protected function getFilesFromPaths(array $paths, array $extensions, array $excluded = array())
    {
        $extensions = array_flip($extensions);
        $files = array();

        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $path;
            } else if (is_dir($path)) {

                $iterator = new \RecursiveDirectoryIterator($path);
                if (!empty($excluded)) {
                    $iterator = new ExcludeRecursiveDirectoryIterator($iterator, $excluded);
                }
                $iterator = new \RecursiveIteratorIterator($iterator);

                /** @var \SplFileInfo[] $iterator */
                foreach ($iterator as $directoryFile) {
                    if (isset($extensions[$directoryFile->getExtension()])) {
                        $files[] = (string) $directoryFile;
                    }
                }
            } else {
                throw new NotExistsPathException($path);
            }
        }

        return $files;
    }
}

class ArrayIterator extends \ArrayIterator
{
    public function getNext()
    {
        $this->next();
        return $this->current();
    }
}

class ExcludeRecursiveDirectoryIterator implements \RecursiveIterator
{
    /** @var array */
    private $excluded = array();

    /** @var \RecursiveDirectoryIterator */
    private $iterator;

    /**
     * @param array $excluded
     * @param \RecursiveDirectoryIterator $iterator
     */
    public function __construct(\RecursiveDirectoryIterator $iterator, array $excluded)
    {
        $this->iterator = $iterator;
        $this->excluded = array_map(array($this, 'normalizePath'), $excluded);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return $this->iterator->current();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->iterator->next();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->iterator->key();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return $this->iterator->valid();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->iterator->rewind();
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Returns if an iterator can be created for the current entry.
     * @link http://php.net/manual/en/recursiveiterator.haschildren.php
     * @return bool true if the current entry can be iterated over, otherwise returns false.
     */
    public function hasChildren()
    {
        $path = $this->normalizePath($this->iterator->getPathname());
        foreach ($this->excluded as $exc) {
            if (strpos($path, $exc) === 0) {
                return false;
            }
        }

        return $this->iterator->hasChildren();
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Returns an iterator for the current entry.
     * @link http://php.net/manual/en/recursiveiterator.getchildren.php
     * @return \RecursiveIterator An iterator for the current entry.
     */
    public function getChildren()
    {
        return new self($this->iterator->getChildren(), $this->excluded);
    }


    /**
     * Source: http://stackoverflow.com/questions/4774116/c-realpath-without-resolving-symlinks
     * @param string $path
     * @return string
     */
    private function normalizePath($path)
    {
        if (!isset($path[0]) || $path[0] !== DIRECTORY_SEPARATOR) {
            $result = explode(DIRECTORY_SEPARATOR, getcwd());
        } else {
            $result = array('');
        }

        $parts = explode(DIRECTORY_SEPARATOR, $path);
        foreach($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            } if ($part === '..') {
                array_pop($result);
            } else {
                $result[] = $part;
            }
        }

        return implode(DIRECTORY_SEPARATOR, $result);
    }
}