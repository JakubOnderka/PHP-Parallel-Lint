<?php

/**
 * @testCase
 */

require __DIR__ . '/../vendor/nette/tester/Tester/bootstrap.php';
require_once __DIR__ . '/../src/Process.php';

use Tester\Assert;

class SkipLintProcessTest extends Tester\TestCase
{
    public function testLargeInput()
    {
        $filesToCheck = array(
            __DIR__ . '/skip-on-5.3/class.php',
            __DIR__ . '/skip-on-5.3/trait.php',
        );

        for ($i = 0; $i < 15; $i++) {
            $filesToCheck = array_merge($filesToCheck, $filesToCheck);
        }

        $process = new \JakubOnderka\PhpParallelLint\SkipLintProcess('php', $filesToCheck);

        while (!$process->isFinished()) {
            usleep(100);
            $process->getChunk();
        }

        foreach ($filesToCheck as $fileToCheck) {
            $status = $process->isSkipped($fileToCheck);
            Assert::notEqual(null, $status);
        }
    }
}

$skipLintProcessTest = new SkipLintProcessTest;
$skipLintProcessTest->run();
