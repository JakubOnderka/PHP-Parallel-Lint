<?php
namespace JakubOnderka\PhpParallelLint;

class Exception extends \Exception implements \JsonSerializable
{
    public function jsonSerialize()
    {
        return array(
            'type' => get_class($this),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
        );
    }
}

class RunTimeException extends Exception
{

}

class InvalidArgumentException extends Exception
{
    protected $argument;

    public function __construct($argument)
    {
        $this->argument = $argument;
        $this->message = "Invalid argument $argument";
    }

    public function getArgument()
    {
        return $this->argument;
    }
}

class NotExistsPathException extends Exception
{
    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
        $this->message = "Path '$path' not found";
    }

    public function getPath()
    {
        return $this->path;
    }
}
