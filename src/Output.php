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

class Output
{
    /** @var int */
    public $filesPerLine = 40;

    /** @var int */
    protected $checkedFiles;

    /** @var int */
    protected $totalFileCount;

    /** @var IWriter */
    protected $writer;

    /**
     * @param IWriter $writer
     */
    public function __construct(IWriter $writer)
    {
        $this->writer = $writer ?: new ConsoleWriter;
    }

    public function ok()
    {
        $this->writer->write('.');
        $this->progress();
    }

    public function error()
    {
        $this->writer->write('X');
        $this->progress();
    }

    public function writeLine($line = null)
    {
        $this->writer->write($line . PHP_EOL);
    }

    public function setTotalFileCount($count)
    {
        $this->totalFileCount = $count;
    }

    protected function progress()
    {
        if (++$this->checkedFiles % $this->filesPerLine === 0) {
            $percent = round($this->checkedFiles/$this->totalFileCount * 100);
            $this->writer->write(" {$this->stringWidth($this->checkedFiles)}/$this->totalFileCount ($percent %)" . PHP_EOL);
        }
    }

    protected function stringWidth($input, $width = 3)
    {
        $multipler = $width - strlen($input);
        return str_repeat(' ', $multipler > 0 ? $multipler : 0) . $input;
    }
}

interface IWriter {
    public function write($string);
}

class ConsoleWriter implements  IWriter
{
    public function write($string)
    {
        echo $string;
    }
}

class FileWriter implements IWriter
{
    /** @var string */
    protected $logFile;

    /** @var string */
    protected $buffer;

    public function __construct($logFile)
    {
        $this->logFile = $logFile;
    }

    public function write($string)
    {
        $this->buffer .= $string;
    }

    public function __destruct()
    {
        file_put_contents($this->logFile, $this->buffer);
    }
}

class MultipleWriter implements IWriter
{
    /** @var IWriter[] */
    protected $writers;

    public function __construct(array $writers)
    {
        foreach ($writers as $writer) {
            $this->setWriter($writer);
        }
    }

    public function setWriter(IWriter $writer)
    {
        $this->writers[] = $writer;
    }

    public function write($string)
    {
        foreach ($this->writers as $writer) {
            $writer->write($string);
        }
    }
}