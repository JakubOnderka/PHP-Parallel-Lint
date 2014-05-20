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

class Result
{
    public $errors;

    public $checkedFiles = 0;

    public $filesWithSyntaxError = 0;

    public $testTime;
}

class ParallelLint
{
    const STATUS_OK = 'ok',
        STATUS_FAIL = 'fail',
        STATUS_ERROR = 'error';

    /** @var int */
    private $parallelJobs;

    /** @var string */
    private $phpExecutable;

    /** @var bool */
    private $aspTagsEnabled = false;

    /** @var bool */
    private $shortTagEnabled = false;

    /** @var callable */
    private $processCallback;

    public function __construct($phpExecutable, $parallelJobs = 10)
    {
        $this->phpExecutable = $phpExecutable;
        $this->parallelJobs = $parallelJobs;
    }

    /**
     * @param array $files
     * @return Result
     */
    public function lint(array $files)
    {
        $result = new Result();
        $processCallback = is_callable($this->processCallback) ? $this->processCallback : function() {};

        /** @var LintProcess[] $running */
        $running = array();

        $startTime = microtime(true);

        while ($files || $running) {
            for ($i = count($running); $files && $i < $this->parallelJobs; $i++) {
                $file = array_shift($files);
                $running[$file] = new LintProcess(
                    $this->phpExecutable,
                    $file,
                    $this->aspTagsEnabled,
                    $this->shortTagEnabled
                );
            }

            usleep(100);

            foreach ($running as $file => $process) {
                if ($process->isFinished()) {
                    unset($running[$file]);

                    if ($process->isFail()) {
                        $processCallback(self::STATUS_FAIL, $file);
                        $result->errors[] = new Error($file, $process->getErrorOutput());
                    } else {
                        $result->checkedFiles++;
                        if ($process->hasSyntaxError()) {
                            $result->errors[] = new SyntaxError($file, $process->getSyntaxError());
                            $result->filesWithSyntaxError++;
                            $processCallback(self::STATUS_ERROR, $file);
                        } else {
                            $processCallback(self::STATUS_OK, $file);
                        }
                    }
                }
            }
        }

        $result->testTime = microtime(true) - $startTime;

        return $result;
    }

    /**
     * @return int
     */
    public function getParallelJobs()
    {
        return $this->parallelJobs;
    }

    /**
     * @param int $parallelJobs
     * @return ParallelLint
     */
    public function setParallelJobs($parallelJobs)
    {
        $this->parallelJobs = $parallelJobs;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhpExecutable()
    {
        return $this->phpExecutable;
    }

    /**
     * @param string $phpExecutable
     * @return ParallelLint
     */
    public function setPhpExecutable($phpExecutable)
    {
        $this->phpExecutable = $phpExecutable;

        return $this;
    }

    /**
     * @return callable
     */
    public function getProcessCallback()
    {
        return $this->processCallback;
    }

    /**
     * @param callable $processCallback
     * @return ParallelLint
     */
    public function setProcessCallback($processCallback)
    {
        $this->processCallback = $processCallback;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAspTagsEnabled()
    {
        return $this->aspTagsEnabled;
    }

    /**
     * @param boolean $aspTagsEnabled
     * @return ParallelLint
     */
    public function setAspTagsEnabled($aspTagsEnabled)
    {
        $this->aspTagsEnabled = $aspTagsEnabled;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShortTagEnabled()
    {
        return $this->shortTagEnabled;
    }

    /**
     * @param boolean $shortTagEnabled
     * @return ParallelLint
     */
    public function setShortTagEnabled($shortTagEnabled)
    {
        $this->shortTagEnabled = $shortTagEnabled;

        return $this;
    }
}