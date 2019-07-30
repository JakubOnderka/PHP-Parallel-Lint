<?php

namespace JakubOnderka\PhpParallelLint;

class Exception extends \Exception implements \JsonSerializable
{
    public function jsonSerialize()
    {
        return [
            'type' => get_class($this),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
        ];
    }
}
