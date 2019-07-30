<?php

namespace JakubOnderka\PhpParallelLint;

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
