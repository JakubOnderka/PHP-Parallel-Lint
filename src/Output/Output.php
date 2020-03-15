<?php
namespace JakubOnderka\PhpParallelLint\Output;

use JakubOnderka\PhpParallelLint\Result;
use JakubOnderka\PhpParallelLint\ErrorFormatter;

interface Output
{
    public function __construct(IWriter $writer);

    public function ok();

    public function skip();

    public function error();

    public function fail();

    public function setTotalFileCount($count);

    public function writeHeader($phpVersion, $parallelJobs, $hhvmVersion = null);

    public function writeResult(Result $result, ErrorFormatter $errorFormatter, $ignoreFails);
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
