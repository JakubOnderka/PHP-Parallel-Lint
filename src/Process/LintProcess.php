<?php
namespace JakubOnderka\PhpParallelLint\Process;

use JakubOnderka\PhpParallelLint\RunTimeException;

class LintProcess extends PhpProcess
{
    const FATAL_ERROR = 'Fatal error';
    const PARSE_ERROR = 'Parse error';

    /**
     * @param PhpExecutable $phpExecutable
     * @param string $fileToCheck Path to file to check
     * @param bool $aspTags
     * @param bool $shortTag
     */
    public function __construct(PhpExecutable $phpExecutable, $fileToCheck, $aspTags = false, $shortTag = false)
    {
        if (empty($fileToCheck)) {
            throw new \InvalidArgumentException("File to check must be set.");
        }

        $parameters = array(
            '-d asp_tags=' . ($aspTags ? 'On' : 'Off'),
            '-d short_open_tag=' . ($shortTag ? 'On' : 'Off'),
            '-d error_reporting=E_ALL',
            '-n',
            '-l',
            escapeshellarg($fileToCheck),
        );

        parent::__construct($phpExecutable, $parameters);
    }

    /**
     * @return bool
     */
    public function hasSyntaxError()
    {
        return $this->containsParserOrFatalError($this->getOutput());
    }

    /**
     * @return string
     * @throws RunTimeException
     */
    public function getSyntaxError()
    {
        if ($this->hasSyntaxError()) {
            foreach (explode("\n", $this->getOutput()) as $line) {
                if ($this->containsParserOrFatalError($line)) {
                    return $line;
                }
            }

            throw new RunTimeException("The output '{$this->getOutput()}' does not contains Parse or Syntax error");
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isFail()
    {
        return defined('PHP_WINDOWS_VERSION_MAJOR') ? $this->getStatusCode() === 1 : parent::isFail();
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->getStatusCode() === 0;
    }

    /**
     * @param $string
     * @return bool
     */
    private function containsParserOrFatalError($string)
    {
        return strpos($string, self::FATAL_ERROR) !== false ||
            strpos($string, self::PARSE_ERROR) !== false;
    }
}