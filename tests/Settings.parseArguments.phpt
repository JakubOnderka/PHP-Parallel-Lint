<?php

/**
 * @testCase
 */

require __DIR__ . '/../vendor/autoload.php';

use JakubOnderka\PhpParallelLint\Settings;
use Tester\Assert;

class SettingsParseArgumentsTest extends Tester\TestCase
{
    public function testNoneArguments()
    {
        $commandLine = "./parallel-lint .";
        $argv = explode(" ", $commandLine);
        $settings = Settings::parseArguments($argv);

        $expectedSettings = new Settings();
        $expectedSettings->phpExecutable = 'php';
        $expectedSettings->shortTag = false;
        $expectedSettings->aspTags = false;
        $expectedSettings->parallelJobs = 10;
        $expectedSettings->extensions = array('php', 'phtml', 'php3', 'php4', 'php5');
        $expectedSettings->paths = array('.');
        $expectedSettings->excluded = array();
        $expectedSettings->colors = Settings::AUTODETECT;
        $expectedSettings->showProgress = true;
        $expectedSettings->json = false;

        Assert::equal($expectedSettings->phpExecutable, $settings->phpExecutable);
        Assert::equal($expectedSettings->shortTag, $settings->shortTag);
        Assert::equal($expectedSettings->aspTags, $settings->aspTags);
        Assert::equal($expectedSettings->parallelJobs, $settings->parallelJobs);
        Assert::equal($expectedSettings->extensions, $settings->extensions);
        Assert::equal($expectedSettings->paths, $settings->paths);
        Assert::equal($expectedSettings->excluded, $settings->excluded);
        Assert::equal($expectedSettings->colors, $settings->colors);
        Assert::equal($expectedSettings->showProgress, $settings->showProgress);
        Assert::equal($expectedSettings->json, $settings->json);
    }

    public function testMoreArguments()
    {
        $commandLine = "./parallel-lint --exclude vendor --no-colors .";
        $argv = explode(" ", $commandLine);
        $settings = Settings::parseArguments($argv);

        $expectedSettings = new Settings();
        $expectedSettings->phpExecutable = 'php';
        $expectedSettings->shortTag = false;
        $expectedSettings->aspTags = false;
        $expectedSettings->parallelJobs = 10;
        $expectedSettings->extensions = array('php', 'phtml', 'php3', 'php4', 'php5');
        $expectedSettings->paths = array('.');
        $expectedSettings->excluded = array('vendor');
        $expectedSettings->colors = Settings::DISABLED;
        $expectedSettings->showProgress = true;
        $expectedSettings->json = false;

        Assert::equal($expectedSettings->phpExecutable, $settings->phpExecutable);
        Assert::equal($expectedSettings->shortTag, $settings->shortTag);
        Assert::equal($expectedSettings->aspTags, $settings->aspTags);
        Assert::equal($expectedSettings->parallelJobs, $settings->parallelJobs);
        Assert::equal($expectedSettings->extensions, $settings->extensions);
        Assert::equal($expectedSettings->paths, $settings->paths);
        Assert::equal($expectedSettings->excluded, $settings->excluded);
        Assert::equal($expectedSettings->colors, $settings->colors);
        Assert::equal($expectedSettings->showProgress, $settings->showProgress);
        Assert::equal($expectedSettings->json, $settings->json);
    }

    public function testColorsForced()
    {
        $commandLine = "./parallel-lint --exclude vendor --colors .";
        $argv = explode(" ", $commandLine);
        $settings = Settings::parseArguments($argv);

        $expectedSettings = new Settings();
        $expectedSettings->colors = Settings::FORCED;

        Assert::equal($expectedSettings->colors, $settings->colors);
    }

    public function testNoProgress()
    {
        $commandLine = "./parallel-lint --exclude vendor --no-progress .";
        $argv = explode(" ", $commandLine);
        $settings = Settings::parseArguments($argv);

        $expectedSettings = new Settings();
        $expectedSettings->showProgress = false;

        Assert::equal($expectedSettings->colors, $settings->colors);
    }
}

$testCase = new SettingsParseArgumentsTest;
$testCase->run();
