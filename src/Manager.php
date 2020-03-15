<?php
namespace JakubOnderka\PhpParallelLint;

use JakubOnderka\PhpParallelLint\Error\Blame;
use JakubOnderka\PhpParallelLint\Error\SyntaxError;
use JakubOnderka\PhpParallelLint\Output;
use JakubOnderka\PhpParallelLint\Process\GitBlameProcess;
use JakubOnderka\PhpParallelLint\Process\PhpExecutable;

class Manager
{
    /** @var Output\Output */
    protected $output;

    /**
     * @param null|Settings $settings
     * @return Result
     * @throws Exception
     * @throws \Exception
     */
    public function run(Settings $settings = null)
    {
        $settings = $settings ?: new Settings;
        $output = $this->output ?: $this->getDefaultOutput($settings);

        $phpExecutable = PhpExecutable::getPhpExecutable($settings->phpExecutable);
        $olderThanPhp54 = $phpExecutable->getVersionId() < 50400; // From PHP version 5.4 are tokens translated by default
        $translateTokens = $phpExecutable->isIsHhvmType() || $olderThanPhp54;

        $output->writeHeader($phpExecutable->getVersionId(), $settings->parallelJobs, $phpExecutable->getHhvmVersion());

        $files = $this->getFilesFromPaths($settings->paths, $settings->extensions, $settings->excluded);

        if (empty($files)) {
            throw new Exception('No file found to check.');
        }

        $output->setTotalFileCount(count($files));

        $parallelLint = new ParallelLint($phpExecutable, $settings->parallelJobs);
        $parallelLint->setAspTagsEnabled($settings->aspTags);
        $parallelLint->setShortTagEnabled($settings->shortTag);
        $parallelLint->setShowDeprecated($settings->showDeprecated);

        $parallelLint->setProcessCallback(function ($status, $file) use ($output) {
            if ($status === ParallelLint::STATUS_OK) {
                $output->ok();
            } else if ($status === ParallelLint::STATUS_SKIP) {
                $output->skip();
            } else if ($status === ParallelLint::STATUS_ERROR) {
                $output->error();
            } else {
                $output->fail();
            }
        });

        $result = $parallelLint->lint($files);

        if ($settings->blame) {
            $this->gitBlame($result, $settings);
        }

        $output->writeResult($result, new ErrorFormatter($settings->colors, $translateTokens), $settings->ignoreFails);

        return $result;
    }

    /**
     * @param Output\Output $output
     */
    public function setOutput(Output\Output $output)
    {
        $this->output = $output;
    }

    /**
     * @param Settings $settings
     * @return Output\Output
     */
    protected function getDefaultOutput(Settings $settings)
    {
        $writer = new Output\ConsoleWriter;
        switch ($settings->format) {
            case Settings::FORMAT_JSON:
                return new Output\JsonOutput($writer);
            case Settings::FORMAT_CHECKSTYLE:
                return new Output\CheckstyleOutput($writer);
        }

        if ($settings->colors === Settings::DISABLED) {
            $output = new Output\TextOutput($writer);
        } else {
            $output = new Output\TextOutputColored($writer, $settings->colors);
        }

        $output->showProgress = $settings->showProgress;

        return $output;
    }

    /**
     * @param Result $result
     * @param Settings $settings
     * @throws Exception
     */
    protected function gitBlame(Result $result, Settings $settings)
    {
        if (!GitBlameProcess::gitExists($settings->gitExecutable)) {
            return;
        }

        foreach ($result->getErrors() as $error) {
            if ($error instanceof SyntaxError) {
                $process = new GitBlameProcess($settings->gitExecutable, $error->getFilePath(), $error->getLine());
                $process->waitForFinish();

                if ($process->isSuccess()) {
                    $blame = new Blame;
                    $blame->name = $process->getAuthor();
                    $blame->email = $process->getAuthorEmail();
                    $blame->datetime = $process->getAuthorTime();
                    $blame->commitHash = $process->getCommitHash();
                    $blame->summary = $process->getSummary();

                    $error->setBlame($blame);
                }
            }
        }
    }

    /**
     * @param array $paths
     * @param array $extensions
     * @param array $excluded
     * @return array
     * @throws NotExistsPathException
     */
    protected function getFilesFromPaths(array $paths, array $extensions, array $excluded = array())
    {
        $extensions = array_flip($extensions);
        $files = array();

        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $path;
            } else if (is_dir($path)) {
                $iterator = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
                if (!empty($excluded)) {
                    $iterator = new RecursiveDirectoryFilterIterator($iterator, $excluded);
                }
                $iterator = new \RecursiveIteratorIterator(
                    $iterator,
                    \RecursiveIteratorIterator::LEAVES_ONLY,
                    \RecursiveIteratorIterator::CATCH_GET_CHILD
                );

                /** @var \SplFileInfo[] $iterator */
                foreach ($iterator as $directoryFile) {
                    if (isset($extensions[pathinfo($directoryFile->getFilename(), PATHINFO_EXTENSION)])) {
                        $files[] = (string) $directoryFile;
                    }
                }
            } else {
                throw new NotExistsPathException($path);
            }
        }

        $files = array_unique($files);

        return $files;
    }
}
