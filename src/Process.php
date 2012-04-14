<?php
namespace ParallelLint;

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

class Process
{
    const STDIN = 0,
        STDOUT = 1,
        STDERR = 2;

    const READ = 'r',
        WRITE = 'w';

    /** @var resource */
    protected $process;

    /** @var resource */
    protected $stdout;

    /** @var string */
    private $output;

    /** @var int */
    private $statusCode;

    /**
     * @param string $cmdLine
     * @param bool $blocking
     */
    public function __construct($cmdLine, $blocking = false)
    {
        $descriptors = array(
            self::STDIN  => array('pipe', self::READ),
            self::STDOUT => array('pipe', self::WRITE),
            self::STDERR => array('pipe', self::WRITE),
        );

        $this->process = proc_open($cmdLine, $descriptors, $pipes, null, null, array('bypass_shell' => true));

        list($stdin, $this->stdout, $stderr) = $pipes;
        fclose($stdin);
        stream_set_blocking($this->stdout, $blocking ? 1 : 0);
        fclose($stderr);
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        $status = proc_get_status($this->process);
        return !$status['running'];
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        $this->terminateAndReadOutput();
        return $this->output;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        $this->terminateAndReadOutput();
        return $this->statusCode;
    }

    /**
     * @throws \Exception
     */
    protected function terminateAndReadOutput()
    {
        if ($this->process !== null) {
            if (!$this->isFinished()) {
                throw new \Exception("Cannot terminate running process");
            }

            $this->output = stream_get_contents($this->stdout);
            fclose($this->stdout);

            $this->statusCode = proc_close($this->process);
            $this->process = null;
        }
    }
}

class LintProcess extends Process
{
    /**
     * @return bool
     */
    public function hasSyntaxError()
    {
        return strpos($this->getOutput(), 'No syntax errors detected') === false;
    }

    /**
     * @return bool|string
     */
    public function getSyntaxError()
    {
        if ($this->hasSyntaxError()) {
            list(, $out) = explode("\n", $this->getOutput());
            return $out;
        }

        return false;
    }
}