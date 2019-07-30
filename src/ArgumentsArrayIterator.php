<?php

namespace JakubOnderka\PhpParallelLint;

class ArgumentsArrayIterator extends \ArrayIterator
{
    public function getNext()
    {
        $this->next();

        return $this->current();
    }
}
