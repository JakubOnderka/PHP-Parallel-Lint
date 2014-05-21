<?php

require_once __DIR__ . '/../vendor/nette/tester/Tester/bootstrap.php';
require_once __DIR__ . '/../src/Error.php';
require_once __DIR__ . '/../src/ParallelLint.php';
require_once __DIR__ . '/../src/Process.php';

use JakubOnderka\PhpParallelLint\ParallelLint;
use Tester\Assert;

class ParallelLintLintTest extends Tester\TestCase
{
    public function testSettersAndGetters()
    {
        $parallelLint = new ParallelLint('php', 10);
        Assert::equal('php', $parallelLint->getPhpExecutable());
        Assert::equal(10, $parallelLint->getParallelJobs());

        $parallelLint->setPhpExecutable('phpd');
        Assert::equal('phpd', $parallelLint->getPhpExecutable());

        $parallelLint->setParallelJobs(33);
        Assert::equal(33, $parallelLint->getParallelJobs());

        $parallelLint->setShortTagEnabled(true);
        Assert::true($parallelLint->isShortTagEnabled());

        $parallelLint->setAspTagsEnabled(true);
        Assert::true($parallelLint->isAspTagsEnabled());

        $parallelLint->setShortTagEnabled(false);
        Assert::false($parallelLint->isShortTagEnabled());

        $parallelLint->setAspTagsEnabled(false);
        Assert::false($parallelLint->isAspTagsEnabled());
    }

    public function testEmptyArray()
    {
        $parallelLint = new ParallelLint('php');
        $result = $parallelLint->lint(array());

        Assert::equal(0, $result->getCheckedFiles());
        Assert::equal(0, $result->getFilesWithSyntaxError());
        Assert::false($result->hasSyntaxError());
        Assert::equal(0, count($result->getErrors()));
    }

    public function testNotExistsFile()
    {
        $parallelLint = new ParallelLint('php');
        $result = $parallelLint->lint(array('path/for-not-found/'));

        Assert::equal(0, $result->getCheckedFiles());
        Assert::equal(0, $result->getFilesWithSyntaxError());
        Assert::false($result->hasSyntaxError());
        Assert::equal(1, count($result->getErrors()));
    }

    public function testEmptyFile()
    {
        $parallelLint = new ParallelLint('php');
        $result = $parallelLint->lint(array(__DIR__ . '/examples/example-01/empty-file'));

        Assert::equal(1, $result->getCheckedFiles());
        Assert::equal(0, $result->getFilesWithSyntaxError());
        Assert::false($result->hasSyntaxError());
        Assert::equal(0, count($result->getErrors()));
    }

    public function testValidFile()
    {
        $parallelLint = new ParallelLint('php');
        $result = $parallelLint->lint(array(__DIR__ . '/examples/example-02/example.php'));

        Assert::equal(1, $result->getCheckedFiles());
        Assert::equal(0, $result->getFilesWithSyntaxError());
        Assert::equal(0, count($result->getErrors()));
    }

    public function testInvalidFile()
    {
        $parallelLint = new ParallelLint('php');
        $result = $parallelLint->lint(array(__DIR__ . '/examples/example-03/example.php'));

        Assert::equal(1, $result->getCheckedFiles());
        Assert::equal(1, $result->getFilesWithSyntaxError());
        Assert::true($result->hasSyntaxError());
        Assert::equal(1, count($result->getErrors()));
    }

    public function testValidAndInvalidFiles()
    {
        $parallelLint = new ParallelLint('php');
        $result = $parallelLint->lint(array(
            __DIR__ . '/examples/example-02/example.php',
            __DIR__ . '/examples/example-03/example.php',
        ));

        Assert::equal(2, $result->getCheckedFiles());
        Assert::equal(1, $result->getFilesWithSyntaxError());
        Assert::true($result->hasSyntaxError());
        Assert::equal(1, count($result->getErrors()));
    }
}

$testCase = new ParallelLintLintTest;
$testCase->run();