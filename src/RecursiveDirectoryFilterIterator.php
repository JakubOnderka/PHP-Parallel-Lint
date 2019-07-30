<?php

namespace JakubOnderka\PhpParallelLint;

class RecursiveDirectoryFilterIterator extends \RecursiveFilterIterator
{
    /** @var \RecursiveDirectoryIterator */
    private $iterator;

    /** @var array */
    private $excluded = [];

    /**
     * @param \RecursiveDirectoryIterator $iterator
     * @param array $excluded
     */
    public function __construct(\RecursiveDirectoryIterator $iterator, array $excluded)
    {
        parent::__construct($iterator);
        $this->iterator = $iterator;
        $this->excluded = array_map([$this, 'getPathname'], $excluded);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Check whether the current element of the iterator is acceptable
     *
     * @link http://php.net/manual/en/filteriterator.accept.php
     * @return bool true if the current element is acceptable, otherwise false.
     */
    public function accept()
    {
        $current = $this->current()->getPathname();
        $current = $this->normalizeDirectorySeparator($current);

        if ('.'.DIRECTORY_SEPARATOR !== $current[0].$current[1]) {
            $current = '.'.DIRECTORY_SEPARATOR.$current;
        }

        return !in_array($current, $this->excluded);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Check whether the inner iterator's current element has children
     *
     * @link http://php.net/manual/en/recursivefilteriterator.haschildren.php
     * @return bool true if the inner iterator has children, otherwise false
     */
    public function hasChildren()
    {
        return $this->iterator->hasChildren();
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Return the inner iterator's children contained in a RecursiveFilterIterator
     *
     * @link http://php.net/manual/en/recursivefilteriterator.getchildren.php
     * @return \RecursiveFilterIterator containing the inner iterator's children.
     */
    public function getChildren()
    {
        return new self($this->iterator->getChildren(), $this->excluded);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathname($file)
    {
        $file = $this->normalizeDirectorySeparator($file);

        if ('.'.DIRECTORY_SEPARATOR !== $file[0].$file[1]) {
            $file = '.'.DIRECTORY_SEPARATOR.$file;
        }

        $directoryFile = new \SplFileInfo($file);

        return $directoryFile->getPathname();
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function normalizeDirectorySeparator($file)
    {
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $file);
    }
}
