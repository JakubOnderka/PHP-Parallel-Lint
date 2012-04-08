<?php
namespace ParallelLint;

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

        $string .= $this->normalizeMessage($this->message);

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
     * @return string
     */
    protected function getShortFilePath()
    {
        return str_replace(getcwd(), '', $this->filePath);
    }

    /**
     * @param int $line
     * @return string
     */
    protected function getCodeSnippet($line)
    {
        $lines = file($this->filePath);

        $string = '';
        $lineStrlen = strlen($line + 2);
        for ($i = $line - 2; $i <= $line + 2; $i++) {
            if (isset($lines[$i])) {
                $string .= ($line === $i ? '  > ' : '    ');
                $string .= $this->stringWidth($i, $lineStrlen) . '| ' . $lines[$i];
            }
        }

        return $string;
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
     * @param string $input
     * @param int $width
     * @return string
     */
    protected function stringWidth($input, $width = 3)
    {
        $multiplier = $width - strlen($input);
        return str_repeat(' ', $multiplier > 0 ? $multiplier : 0) . $input;
    }
}