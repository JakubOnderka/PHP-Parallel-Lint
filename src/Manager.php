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

class Manager
{
    const CODE_OK = 0,
        CODE_ERROR = 255;

    /** @var Output */
    protected $output;

    /**
     * @param null|Settings $settings
     * @return bool
     * @throws \Exception
     */
    public function run(Settings $settings = null)
    {
        $settings = $settings ?: new Settings;
        $output = $this->output ?: ($settings->colors ? new OutputColored(new ConsoleWriter) : new Output(new ConsoleWriter));

        $version = $this->getPhpExecutableVersion($settings->phpExecutable);
        $translateTokens = $version < 50400; // From PHP version 5.4 are tokens translated by default

        $output->write("PHP {$this->phpVersionIdToString($version)} | ");

        if ($settings->parallelJobs === 1) {
            $output->writeLine("1 job");
        } else {
            $output->writeLine("{$settings->parallelJobs} parallel jobs");
        }

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

            usleep(100);

            foreach ($running as $file => $process) {
                if ($process->isFinished()) {
                    unset($running[$file]);

                    if ($process->isFail()) {
                        $output->fail();
                        $errors[] = new Error($file, $process->getErrorOutput());
                    } else {
                        $checkedFiles++;
                        if ($process->hasSyntaxError()) {
                            if ($settings->colors) {
                                $errors[] = new SyntaxErrorColored($file, $process->getSyntaxError(), $translateTokens);
                            } else {
                                $errors[] = new SyntaxError($file, $process->getSyntaxError(), $translateTokens);
                            }

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

        if ($filesWithSyntaxError === 0) {
            $output->writeLine($message, Output::TYPE_OK);
        } else {
            $output->writeLine($message, Output::TYPE_ERROR);
        }

        if (!empty($errors)) {
            $output->writeNewLine();

            foreach ($errors as $errorMessage) {
                $output->writeLine(str_repeat('-', 60));
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
     * @return int PHP version as PHP_VERSION_ID constant
     * @throws \Exception
     */
    protected function getPhpExecutableVersion($phpExecutable)
    {
        exec(escapeshellarg($phpExecutable) . ' -v', $output, $result);

        if ($result !== self::CODE_OK && $result !== self::CODE_ERROR) {
            throw new \Exception("Unable to execute '{$phpExecutable}'.");
        }

        if (!preg_match('~PHP ([0-9]*).([0-9]*).([0-9]*)~', $output[0], $matches)) {
            throw new \Exception("'{$phpExecutable}' is not valid PHP binary.");
        }

        $phpVersionId = $matches[1] * 10000 + $matches[2] * 100 + $matches[3];

        return $phpVersionId;
    }

    /**
     * @param int $phpVersionId
     * @return string
     */
    protected function phpVersionIdToString($phpVersionId)
    {
        $releaseVersion = (int)substr($phpVersionId, -2, 2);
        $minorVersion = (int)substr($phpVersionId, -4, 2);
        $majorVersion = (int)substr($phpVersionId, 0, strlen($phpVersionId)-4);

        return "$majorVersion.$minorVersion.$releaseVersion";
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
        $cmdLine .= ' -d error_reporting=E_ALL';
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
                    $iterator = new RecursiveDirectoryFilterIterator($iterator, $excluded);
                }
                $iterator = new \RecursiveIteratorIterator($iterator);

                /** @var \SplFileInfo[] $iterator */
                foreach ($iterator as $directoryFile) {
                    if (isset($extensions[pathinfo($directoryFile->getFilename(), PATHINFO_EXTENSION)])) {
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

class RecursiveDirectoryFilterIterator extends \RecursiveFilterIterator
{
    /** @var \RecursiveDirectoryIterator */
    private $iterator;

    /** @var array */
    private $excluded = array();

    /**
     * @param \RecursiveDirectoryIterator $iterator
     * @param array $excluded
     */
    public function __construct(\RecursiveDirectoryIterator $iterator, array $excluded)
    {
        parent::__construct($iterator);
        $this->iterator = $iterator;
        $this->excluded = array_map(array($this, 'getPathname'), $excluded);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Check whether the current element of the iterator is acceptable
     *
     * @link http://php.net/manual/en/filteriterator.accept.php
     * @return bool true if the current element is acceptable, otherwise false.
     */
    public function accept()
    {
        $current = $this->current()->getPathname();

        if(".".DIRECTORY_SEPARATOR !== $current[0].$current[1]) {
            $current = ".".DIRECTORY_SEPARATOR.$current;
        }

        return !in_array($current, $this->excluded);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Check whether the inner iterator's current element has children
     *
     * @link http://php.net/manual/en/recursivefilteriterator.haschildren.php
     * @return bool true if the inner iterator has children, otherwise false
     */
    public function hasChildren()
    {
        return $this->iterator->hasChildren();
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Return the inner iterator's children contained in a RecursiveFilterIterator
     *
     * @link http://php.net/manual/en/recursivefilteriterator.getchildren.php
     * @return \RecursiveFilterIterator containing the inner iterator's children.
     */
    public function getChildren()
    {
        return new self($this->iterator->getChildren(), $this->excluded);
    }

    /**
     * @param string $excluded
     * @return string
     */
    private function getPathname($file)
    {
        if(".".DIRECTORY_SEPARATOR !== $file[0].$file[1]) {
            $file = ".".DIRECTORY_SEPARATOR.$file;
        }
        
        $directoryFile = new \SplFileInfo($file);
        return $directoryFile->getPathname();
    }
}
