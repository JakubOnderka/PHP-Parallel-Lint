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


class ArrayIterator extends \ArrayIterator
{
    public function getNext()
    {
        $this->next();
        return $this->current();
    }
}

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
     */
    public function parseArguments(array $arguments)
    {
        $arguments = new ArrayIterator(array_slice($arguments, 1));
        $setting = new Settings;

        foreach ($arguments as $argument) {
            if ($argument{0} !== '-') {
                $setting->paths[] = $argument;
            } else {
                switch (substr($argument, 1)) {
                    case 'p':
                        $setting->phpExecutable = $arguments->getNext();
                        break;

                    case 'log':
                        $setting->logFile = $arguments->getNext();
                        break;

                    case 'short':
                        $setting->shortTag = true;
                        break;

                    case 'asp':
                        $setting->aspTags = true;
                        break;

                    case 'e':
                        $setting->extensions = array_map('trim', explode(',', $arguments->getNext()));
                        break;

                    case 'j':
                        $setting->parallelJobs = max((int) $arguments->getNext(), 1);
                        break;

                    default:
                        throw new InvalidArgumentException($argument);
                }
            }
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

        $this->checkPhpExecutableExists($settings->phpExecutable);

        $cmdLine = $this->getCmdLine($settings);
        $files = $this->getFilesFromPaths($settings->paths, $settings->extensions);

        $output = $this->output ?: new Output(new ConsoleWriter);
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

        $colorReplace = array(
            '#.+no syntax error found$#m' => "\033[42m\033[1;37m\\0\033[0m",
            '#.+syntax error found in .+ file(s)*$#m' => "\033[1;41m\033[37m\\0\033[0m",
        );
        $message = preg_replace(array_keys($colorReplace), $colorReplace, $message);

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
     * @return array
     * @throws NotExistsPathException
     */
    protected function getFilesFromPaths(array $paths, array $extensions)
    {
        $extensions = array_flip($extensions);
        $files = array();

        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $path;
            } else if (is_dir($path)) {
                $directoryFiles = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
                foreach ($directoryFiles as $directoryFile) {
                    $directoryFile = (string) $directoryFile;
                    if (isset($extensions[pathinfo($directoryFile, PATHINFO_EXTENSION)])) {
                        $files[] = $directoryFile;
                    }
                }
            } else {
                throw new NotExistsPathException($path);
            }
        }

        return $files;
    }
}