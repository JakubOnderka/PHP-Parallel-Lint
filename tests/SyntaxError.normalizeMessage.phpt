<?php

/**
 * @testCase
 */

require __DIR__ . '/../vendor/autoload.php';

use JakubOnderka\PhpParallelLint\SyntaxError;
use Tester\Assert;

class SyntaxErrorNormalizeMessageTest extends Tester\TestCase
{
    public function testInWordInErrorMessage()
    {
        $message = 'Fatal error: \'break\' not in the \'loop\' or \'switch\' context in test.php on line 2';
        $error = new SyntaxError('test.php', $message);
        Assert::equal('\'break\' not in the \'loop\' or \'switch\' context', $error->getNormalizedMessage());
    }

    public function testInWordInErrorMessageAndInFileName()
    {
        $message = 'Fatal error: \'break\' not in the \'loop\' or \'switch\' context in test in file.php on line 2';
        $error = new SyntaxError('test in file.php', $message);
        Assert::equal('\'break\' not in the \'loop\' or \'switch\' context', $error->getNormalizedMessage());
    }
}

$testCase = new SyntaxErrorNormalizeMessageTest;
$testCase->run();