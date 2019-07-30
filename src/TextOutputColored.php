<?php

namespace JakubOnderka\PhpParallelLint;

use JakubOnderka\PhpConsoleColor\ConsoleColor;
use JakubOnderka\PhpConsoleColor\InvalidStyleException;

class TextOutputColored extends TextOutput
{
    /**
     * @var ConsoleColor
     */
    private $colors;

    public function __construct(IWriter $writer, $colors = Settings::AUTODETECT)
    {
        parent::__construct($writer);

        if (class_exists('\JakubOnderka\PhpConsoleColor\ConsoleColor')) {
            $this->colors = new ConsoleColor();
            $this->colors->setForceStyle($colors === Settings::FORCED);
        }
    }

    /**
     * @param string $string
     * @param string $type
     *
     * @throws InvalidStyleException
     */
    public function write($string, $type = self::TYPE_DEFAULT)
    {
        if (!$this->colors instanceof ConsoleColor) {
            parent::write($string, $type);
        } else {
            switch ($type) {
                case self::TYPE_OK:
                    parent::write($this->colors->apply('bg_green', $string));
                    break;

                case self::TYPE_SKIP:
                    parent::write($this->colors->apply('bg_yellow', $string));
                    break;

                case self::TYPE_ERROR:
                    parent::write($this->colors->apply('bg_red', $string));
                    break;

                default:
                    parent::write($string);
            }
        }
    }
}
