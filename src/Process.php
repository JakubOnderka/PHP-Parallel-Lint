<?php
namespace ParallelLint;

/*
Copyright (c) 2012, Jakub Onderka
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
 * Redistributions of source code must retain the above copyright
notice, this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright
notice, this list of conditions and the following disclaimer in the
documentation and/or other materials provided with the distribution.
 * Neither the name of the <organization> nor the
names of its contributors may be used to endorse or promote products
derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

class Process
{
    /** @var resource */
    protected $process;

    /** @var resource */
    protected $stdout;

    /** @var string */
    protected $output;

    public function __construct($cmdLine, $blocking = false)
    {
        $descriptors = array(
			array('pipe', 'r'),
			array('pipe', 'w'),
			array('pipe', 'w'),
		);

        $this->process = proc_open($cmdLine, $descriptors, $pipes, null, null, array('bypass_shell' => true));

        list($stdin, $this->stdout, $stderr) = $pipes;
        fclose($stdin);
        //stream_set_blocking($this->stdout, $blocking ? 1 : 0);
        fclose($stderr);
    }

    public function isReady()
    {
        $status = proc_get_status($this->process);
        return !$status['running'];
    }

    public function getResults()
    {
        $this->output = stream_get_contents($this->stdout);
        fclose($this->stdout);

        return proc_close($this->process);
    }

    public function getOutput()
    {
        return $this->output;
    }
}

class LintProcess extends Process
{
    /**
     * @return bool
     */
    public function hasSyntaxError()
    {
        return strpos($this->output, 'No syntax errors detected') === false;
    }

    /**
     * @return bool|string
     */
    public function getSyntaxError()
    {
        if ($this->hasSyntaxError()) {
            list(, $out) = explode("\n", $this->output);
            return $out;
        }

        return false;
    }
}