<?php

namespace JakubOnderka\PhpParallelLint;

class TextOutput implements Output
{
    const TYPE_DEFAULT = 'default',
        TYPE_SKIP = 'skip',
        TYPE_ERROR = 'error',
        TYPE_FAIL = 'fail',
        TYPE_OK = 'ok';

    /**
     * @var int
     */
    public $filesPerLine = 60;

    /**
     * @var bool
     */
    public $showProgress = true;

    /**
     * @var int
     */
    protected $checkedFiles;

    /**
     * @var int
     */
    protected $totalFileCount;

    /**
     * @var IWriter
     */
    protected $writer;

    public function __construct(IWriter $writer)
    {
        $this->writer = $writer;
    }

    public function ok()
    {
        $this->writeMark(self::TYPE_OK);
    }

    public function skip()
    {
        $this->writeMark(self::TYPE_SKIP);
    }

    public function error()
    {
        $this->writeMark(self::TYPE_ERROR);
    }

    public function fail()
    {
        $this->writeMark(self::TYPE_FAIL);
    }

    /**
     * @param string $string
     * @param string $type
     */
    public function write($string, $type = self::TYPE_DEFAULT)
    {
        $this->writer->write($string);
    }

    /**
     * @param string|null $line
     * @param string $type
     */
    public function writeLine($line = null, $type = self::TYPE_DEFAULT)
    {
        $this->write($line, $type);
        $this->writeNewLine();
    }

    /**
     * @param int $count
     */
    public function writeNewLine($count = 1)
    {
        $this->write(str_repeat(PHP_EOL, $count));
    }

    /**
     * @param int $count
     */
    public function setTotalFileCount($count)
    {
        $this->totalFileCount = $count;
    }

    /**
     * @param int $phpVersion
     * @param int $parallelJobs
     * @param string $hhvmVersion
     */
    public function writeHeader($phpVersion, $parallelJobs, $hhvmVersion = null)
    {
        $this->write("PHP {$this->phpVersionIdToString($phpVersion)} | ");

        if ($hhvmVersion) {
            $this->write("HHVM $hhvmVersion | ");
        }

        if ($parallelJobs === 1) {
            $this->writeLine("1 job");
        } else {
            $this->writeLine("{$parallelJobs} parallel jobs");
        }
    }

    /**
     * @param Result $result
     * @param ErrorFormatter $errorFormatter
     * @param bool $ignoreFails
     */
    public function writeResult(Result $result, ErrorFormatter $errorFormatter, $ignoreFails)
    {
        if ($this->showProgress) {
            if ($this->checkedFiles % $this->filesPerLine !== 0) {
                $rest = $this->filesPerLine - ($this->checkedFiles % $this->filesPerLine);
                $this->write(str_repeat(' ', $rest));
                $this->writePercent();
            }

            $this->writeNewLine(2);
        }

        $testTime = round($result->getTestTime(), 1);
        $message = "Checked {$result->getCheckedFilesCount()} files in $testTime ";
        $message .= $testTime == 1 ? 'second' : 'seconds';

        if ($result->getSkippedFilesCount() > 0) {
            $message .= ", skipped {$result->getSkippedFilesCount()} ";
            $message .= ($result->getSkippedFilesCount() === 1 ? 'file' : 'files');
        }

        $this->writeLine($message);

        if (!$result->hasSyntaxError()) {
            $message = "No syntax error found";
        } else {
            $message = "Syntax error found in {$result->getFilesWithSyntaxErrorCount()} ";
            $message .= ($result->getFilesWithSyntaxErrorCount() === 1 ? 'file' : 'files');
        }

        if ($result->hasFilesWithFail()) {
            $message .= ", failed to check {$result->getFilesWithFailCount()} ";
            $message .= ($result->getFilesWithFailCount() === 1 ? 'file' : 'files');

            if ($ignoreFails) {
                $message .= ' (ignored)';
            }
        }

        $hasError = $ignoreFails ? $result->hasSyntaxError() : $result->hasError();
        $this->writeLine($message, $hasError ? self::TYPE_ERROR : self::TYPE_OK);

        if ($result->hasError()) {
            $this->writeNewLine();
            foreach ($result->getErrors() as $error) {
                $this->writeLine(str_repeat('-', 60));
                $this->writeLine($errorFormatter->format($error));
            }
        }
    }

    protected function writeMark($type)
    {
        ++$this->checkedFiles;

        if ($this->showProgress) {
            if ($type === self::TYPE_OK) {
                $this->writer->write('.');
            } else if ($type === self::TYPE_SKIP) {
                $this->write('S', self::TYPE_SKIP);
            } else if ($type === self::TYPE_ERROR) {
                $this->write('X', self::TYPE_ERROR);
            } else if ($type === self::TYPE_FAIL) {
                $this->writer->write('-');
            }

            if ($this->checkedFiles % $this->filesPerLine === 0) {
                $this->writePercent();
            }
        }
    }

    protected function writePercent()
    {
        $percent = floor($this->checkedFiles / $this->totalFileCount * 100);
        $current = $this->stringWidth($this->checkedFiles, strlen($this->totalFileCount));
        $this->writeLine(" $current/$this->totalFileCount ($percent %)");
    }

    /**
     * @param string $input
     * @param int $width
     *
     * @return string
     */
    protected function stringWidth($input, $width = 3)
    {
        $multiplier = $width - strlen($input);

        return str_repeat(' ', $multiplier > 0 ? $multiplier : 0).$input;
    }

    /**
     * @param int $phpVersionId
     *
     * @return string
     */
    protected function phpVersionIdToString($phpVersionId)
    {
        $releaseVersion = (int) substr($phpVersionId, -2, 2);
        $minorVersion = (int) substr($phpVersionId, -4, 2);
        $majorVersion = (int) substr($phpVersionId, 0, strlen($phpVersionId) - 4);

        return "$majorVersion.$minorVersion.$releaseVersion";
    }
}
