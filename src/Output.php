<?php
namespace ParallelLint;

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