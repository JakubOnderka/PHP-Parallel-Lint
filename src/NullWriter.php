<?php

namespace JakubOnderka\PhpParallelLint;

class NullWriter implements IWriter
{
    /**
     * @param string $string
     */
    public function write($string)
    {

    }
}
