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

class Output
{
    const TYPE_DEFAULT = 'default',
        TYPE_SKIP = 'skip',
        TYPE_ERROR = 'error',
        TYPE_OK = 'ok';

    /** @var int */
    public $filesPerLine = 60;

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

    public function skip()
    {
        $this->write('S', self::TYPE_SKIP);
        $this->progress();
    }

    public function error()
    {
        $this->write('X', self::TYPE_ERROR);
        $this->progress();
    }

    public function fail()
    {
        $this->writer->write('-');
        $this->progress();
    }

    /**
     * @param string $string
     * @param string $type
     */
    public function write($string, $type = self::TYPE_DEFAULT)
    {
        $this->writer->write($string);
    }

    /**
     * @param string|null $line
     * @param string $type
     */
    public function writeLine($line = null, $type = self::TYPE_DEFAULT)
    {
        $this->write($line, $type);
        $this->writeNewLine();
    }

    /**
     * @param int $count
     */
    public function writeNewLine($count = 1)
    {
        $this->write(str_repeat(PHP_EOL, $count));
    }

    /**
     * @param int $count
     */
    public function setTotalFileCount($count)
    {
        $this->totalFileCount = $count;
    }

    protected function progress()
    {
        if (++$this->checkedFiles % $this->filesPerLine === 0) {
            $percent = round($this->checkedFiles/$this->totalFileCount * 100);
            $current = $this->stringWidth($this->checkedFiles, strlen($this->totalFileCount));
            $this->writeLine(" $current/$this->totalFileCount ($percent %)");
        }
    }

    /**
     * @param string $input
     * @param int $width
     * @return string
     */
    protected function stringWidth($input, $width = 3)
    {
        $multiplier = $width - strlen($input);
        return str_repeat(' ', $multiplier > 0 ? $multiplier : 0) . $input;
    }
}

class OutputColored extends Output
{
    /** @var \JakubOnderka\PhpConsoleColor\ConsoleColor */
    private $colors;

    public function __construct(IWriter $writer)
    {
        parent::__construct($writer);

        if (class_exists('\JakubOnderka\PhpConsoleColor\ConsoleColor')) {
            $this->colors = new \JakubOnderka\PhpConsoleColor\ConsoleColor();
            $this->colors->setForceStyle(true);
        }
    }

    /**
     * @param string $string
     * @param string $type
     * @throws \JakubOnderka\PhpConsoleColor\InvalidStyleException
     */
    public function write($string, $type = self::TYPE_DEFAULT)
    {
        if (!$this->colors instanceof \JakubOnderka\PhpConsoleColor\ConsoleColor) {
            parent::write($string, $type);
        } else {
            switch ($type) {
                case self::TYPE_OK:
                    parent::write($this->colors->apply('bg_green', $string));
                    break;

                case self::TYPE_SKIP:
                    parent::write($this->colors->apply('bg_yellow', $string));
                    break;

                case self::TYPE_ERROR:
                    parent::write($this->colors->apply('bg_red', $string));
                    break;

                default:
                    parent::write($string);
            }
        }
    }
}

interface IWriter
{
    /**
     * @param string $string
     */
    public function write($string);
}

class NullWriter implements IWriter
{
    /**
     * @param string $string
     */
    public function write($string)
    {

    }
}

class ConsoleWriter implements IWriter
{
    /**
     * @param string $string
     */
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

    /**
     * @param IWriter[] $writers
     */
    public function __construct(array $writers)
    {
        foreach ($writers as $writer) {
            $this->addWriter($writer);
        }
    }

    /**
     * @param IWriter $writer
     */
    public function addWriter(IWriter $writer)
    {
        $this->writers[] = $writer;
    }

    /**
     * @param $string
     */
    public function write($string)
    {
        foreach ($this->writers as $writer) {
            $writer->write($string);
        }
    }
}
