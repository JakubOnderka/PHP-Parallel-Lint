<?php

namespace JakubOnderka\PhpParallelLint;

class SyntaxError extends Error
{
    /** @var Blame */
    private $blame;

    /**
     * @return int|null
     */
    public function getLine()
    {
        preg_match('~on line ([0-9]+)$~', $this->message, $matches);

        if ($matches && isset($matches[1])) {
            $onLine = (int) $matches[1];

            return $onLine;
        }

        return null;
    }

    /**
     * @param bool $translateTokens
     *
     * @return mixed|string
     */
    public function getNormalizedMessage($translateTokens = false)
    {
        $message = preg_replace('~^(Parse|Fatal) error: (syntax error, )?~', '', $this->message);
        $message = preg_replace('~ in '.preg_quote(basename($this->filePath)).' on line [0-9]+$~', '', $message);
        $message = ucfirst($message);

        if ($translateTokens) {
            $message = $this->translateTokens($message);
        }

        return $message;
    }

    /**
     * @param Blame $blame
     */
    public function setBlame(Blame $blame)
    {
        $this->blame = $blame;
    }

    /**
     * @return Blame
     */
    public function getBlame()
    {
        return $this->blame;
    }

    /**
     * @param string $message
     *
     * @return string
     */
    protected function translateTokens($message)
    {
        static $translateTokens = [
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
            'T_ECHO' => 'echo',
        ];

        return preg_replace_callback(
            '~T_([A-Z_]*)~',
            function ($matches) use ($translateTokens) {
                list($tokenName) = $matches;
                if (isset($translateTokens[$tokenName])) {
                    $operator = $translateTokens[$tokenName];

                    return "$operator ($tokenName)";
                }

                return $tokenName;
            },
            $message
        );
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
            'type' => 'syntaxError',
            'file' => $this->getFilePath(),
            'line' => $this->getLine(),
            'message' => $this->getMessage(),
            'normalizeMessage' => $this->getNormalizedMessage(),
            'blame' => $this->blame,
        ];
    }
}
