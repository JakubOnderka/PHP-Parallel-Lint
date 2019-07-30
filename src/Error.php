<?php

namespace JakubOnderka\PhpParallelLint;

class Error implements \JsonSerializable
{
    /** @var string */
    protected $filePath;

    /** @var string */
    protected $message;

    /**
     * @param string $filePath
     * @param string $message
     */
    public function __construct($filePath, $message)
    {
        $this->filePath = $filePath;
        $this->message = rtrim($message);
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @return string
     */
    public function getShortFilePath()
    {
        $cwd = getcwd();

        if ($cwd === '/') {
            // For root directory in unix, do not modify path
            return $this->filePath;
        }

        return preg_replace('/'.preg_quote($cwd, '/').'/', '', $this->filePath, 1);
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return [
            'type' => 'error',
            'file' => $this->getFilePath(),
            'message' => $this->getMessage(),
        ];
    }
}
