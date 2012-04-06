<?php
namespace ParallelLint;

class InvalidArgumentException extends \Exception
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

class NotExistsPathException extends \Exception
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