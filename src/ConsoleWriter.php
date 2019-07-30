<?php

namespace JakubOnderka\PhpParallelLint;

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
