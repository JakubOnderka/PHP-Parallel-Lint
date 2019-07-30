<?php

namespace JakubOnderka\PhpParallelLint;

interface IWriter
{
    /**
     * @param string $string
     */
    public function write($string);
}
