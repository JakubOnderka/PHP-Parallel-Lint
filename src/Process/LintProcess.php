<?php
namespace JakubOnderka\PhpParallelLint\Process;

use JakubOnderka\PhpParallelLint\RunTimeException;

class LintProcess extends PhpProcess
{
    const FATAL_ERROR = 'Fatal error';
    const PARSE_ERROR = 'Parse error';
    const DEPRECATED_ERROR = 'Deprecated:';

    /**
     * @var bool
     */
    private $showDeprecatedErrors;

    /**
     * @param PhpExecutable $phpExecutable
     * @param string $fileToCheck Path to file to check
     * @param bool $aspTags
     * @param bool $shortTag
     * @param bool $deprecated
     */
    public function __construct(PhpExecutable $phpExecutable, $fileToCheck, $aspTags = false, $shortTag = false, $deprecated = false)
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

        $this->showDeprecatedErrors = $deprecated;
        parent::__construct($phpExecutable, $parameters);
    }

    /**
     * @return bool
     */
    public function containsError()
    {
        return $this->containsParserOrFatalError($this->getOutput());
    }

    /**
     * @return string
     * @throws RunTimeException
     */
    public function getSyntaxError()
    {
        if ($this->containsError()) {
            foreach (explode("\n", $this->getOutput()) as $line) {
                if ($this->containsFatalError($line)) {
                    return $line;
                }
            }

            // Look for parser errors second
            foreach (explode("\n", $this->getOutput()) as $line) {
                if ($this->containsParserError($line)) {
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
        return $this->containsParserError($string) || $this->containsFatalError($string);
    }

    /**
     * @param $string
     * @return bool
     */
    private function containsParserError($string)
    {
        return strpos($string, self::PARSE_ERROR) !== false;
    }

    /**
     * @param $string
     * @return bool
     */
    private function containsFatalError($string)
    {
        return strpos($string, self::FATAL_ERROR) !== false ||
            strpos($string, self::PARSE_ERROR) !== false ||
            $this->containsDeprecatedError($string);
    }

    private function containsDeprecatedError($string)
    {
        if ($this->showDeprecatedErrors === false) {
            return false;
        }

        return strpos($string, self::DEPRECATED_ERROR) !== false;
    }
}
