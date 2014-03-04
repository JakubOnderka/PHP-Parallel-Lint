<?php
namespace JakubOnderka\PhpParallelLint;

/*
Copyright (c) 2012, Jakub Onderka
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those
of the authors and should not be interpreted as representing official policies,
either expressed or implied, of the FreeBSD Project.
 */

use JakubOnderka\PhpConsoleColor\ConsoleColor;
use JakubOnderka\PhpConsoleHighlighter\Highlighter;

class Error
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
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->message;
    }
}

class SyntaxError extends Error
{
    /**
     * @param bool $withCodeSnipped
     * @return string
     */
    public function getString($withCodeSnipped = true)
    {
        $string = "Parse error: {$this->getShortFilePath()}";

        preg_match('~on line ([0-9]*)~', $this->message, $matches);

        if ($matches && isset($matches[1])) {
            $onLine = (int) $matches[1];
            $string .= ":$onLine" . PHP_EOL;

            if ($withCodeSnipped) {
                $string .= $this->getCodeSnippet($onLine);
            }
        }

        $string .= $this->translateToken($this->normalizeMessage($this->message));

        return $string;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getString();
    }

    /**
     * @param int $lineNumber
     * @param int $linesBefore
     * @param int $linesAfter
     * @return string
     */
    protected function getCodeSnippet($lineNumber, $linesBefore = 2, $linesAfter = 2)
    {
        $lines = file($this->filePath);

        $offset = $lineNumber - $linesBefore - 1;
        $offset = max($offset, 0);
        $length = $linesAfter + $linesBefore + 1;
        $lines = array_slice($lines, $offset, $length, $preserveKeys = true);

        end($lines);
        $lineStrlen = strlen(key($lines) + 1);

        $snippet = '';
        foreach ($lines as $i => $line) {
            $snippet .= ($lineNumber === $i + 1 ? '  > ' : '    ');
            $snippet .= str_pad($i + 1, $lineStrlen, ' ', STR_PAD_LEFT) . '| ' . rtrim($line) . PHP_EOL;
        }

        return $snippet;
    }

    /**
     * @param string $message
     * @return string
     */
    protected function normalizeMessage($message)
    {
        $message = str_replace('Parse error: syntax error, ', '', $message);
        $message = ucfirst($message);
        return preg_replace('~ in (.*) on line [0-9]*~', '', $message);
    }

    /**
     * @param string $message
     * @return string
     */
    protected function translateToken($message)
    {
        static $translateTokens = array(
            'T_FILE' => '__FILE__',
            'T_FUNC_C' => '__FUNCTION__',
            'T_HALT_COMPILER' => '__halt_compiler()',
            'T_INC' => '++',
            'T_IS_EQUAL' => '==',
            'T_IS_GREATER_OR_EQUAL' => '>=',
            'T_IS_IDENTICAL' => '===',
            'T_IS_NOT_IDENTICAL' => '!==',
            'T_IS_SMALLER_OR_EQUAL' => '<=',
            'T_LINE' => '__LINE__',
            'T_METHOD_C' => '__METHOD__',
            'T_MINUS_EQUAL' => '-=',
            'T_MOD_EQUAL' => '%=',
            'T_MUL_EQUAL' => '*=',
            'T_NS_C' => '__NAMESPACE__',
            'T_NS_SEPARATOR' => '\\',
            'T_OBJECT_OPERATOR' => '->',
            'T_OR_EQUAL' => '|=',
            'T_PAAMAYIM_NEKUDOTAYIM' => '::',
            'T_PLUS_EQUAL' => '+=',
            'T_SL' => '<<',
            'T_SL_EQUAL' => '<<=',
            'T_SR' => '>>',
            'T_SR_EQUAL' => '>>=',
            'T_START_HEREDOC' => '<<<',
            'T_XOR_EQUAL' => '^=',
        );

        return preg_replace_callback('~T_([A-Z_]*)~', function($matches) use($translateTokens) {
            list($tokenName) = $matches;
            if (isset($translateTokens[$tokenName])) {
                $operator = $translateTokens[$tokenName];
                return "$tokenName ($operator)";
            }

            return $tokenName;
        }, $message);
    }

    /**
     * @param string $input
     * @param int $width
     * @return string
     */
    protected function stringWidth($input, $width = 3)
    {
        $multiplier = $width - strlen($input);
        return str_repeat(' ', $multiplier > 0 ? $multiplier : 0) . $input;
    }

    /**
     * @return string
     */
    protected function getShortFilePath()
    {
        return str_replace(getcwd(), '', $this->filePath);
    }
}

class SyntaxErrorColored extends SyntaxError
{
    /**
     * @param int $lineNumber
     * @param int $linesBefore
     * @param int $linesAfter
     * @return string
     */
    protected function getCodeSnippet($lineNumber, $linesBefore = 2, $linesAfter = 2)
    {
        if (!class_exists('\JakubOnderka\PhpConsoleHighlighter\Highlighter')) {
            return parent::getCodeSnippet($lineNumber, $linesBefore, $linesAfter);
        }

        $colors = new ConsoleColor();
        $colors->setForceStyle(true);
        $highlighter = new Highlighter($colors);

        $fileContent = file_get_contents($this->filePath);
        return $highlighter->getCodeSnippet($fileContent, $lineNumber, $linesBefore, $linesAfter);
    }
}