<?php

require __DIR__ . '/../vendor/nette/tester/Tester/bootstrap.php';
require_once __DIR__ . '/../src/Manager.php';
require_once __DIR__ . '/../src/Settings.php';

use JakubOnderka\PhpParallelLint\Manager;
use JakubOnderka\PhpParallelLint\Settings;
use Tester\Assert;



// test command line string "./parallel-lint ."
$manager = new Manager();
$commandLine = "./parallel-lint .";
$argv = explode(" ", $commandLine);
$settings = $manager->parseArguments($argv);

$expectedSettings = new Settings();
$expectedSettings->phpExecutable = 'php';
$expectedSettings->shortTag = false;
$expectedSettings->aspTags = false;
$expectedSettings->parallelJobs = 10;
$expectedSettings->extensions = array('php', 'phtml', 'php3', 'php4', 'php5');
$expectedSettings->paths = array('.');
$expectedSettings->excluded = array();
$expectedSettings->colors = true;

Assert::equal($expectedSettings->phpExecutable, $settings->phpExecutable);
Assert::equal($expectedSettings->shortTag, $settings->shortTag);
Assert::equal($expectedSettings->aspTags, $settings->aspTags);
Assert::equal($expectedSettings->parallelJobs, $settings->parallelJobs);
Assert::equal($expectedSettings->extensions, $settings->extensions);
Assert::equal($expectedSettings->paths, $settings->paths);
Assert::equal($expectedSettings->excluded, $settings->excluded);
Assert::equal($expectedSettings->colors, $settings->colors);



// test command line string "./parallel-lint --exclude vendor --no-colors ."
$manager = new Manager();
$commandLine = "./parallel-lint --exclude vendor --no-colors .";
$argv = explode(" ", $commandLine);
$settings = $manager->parseArguments($argv);

$expectedSettings = new Settings();
$expectedSettings->phpExecutable = 'php';
$expectedSettings->shortTag = false;
$expectedSettings->aspTags = false;
$expectedSettings->parallelJobs = 10;
$expectedSettings->extensions = array('php', 'phtml', 'php3', 'php4', 'php5');
$expectedSettings->paths = array('.');
$expectedSettings->excluded = array('vendor');
$expectedSettings->colors = false;

Assert::equal($expectedSettings->phpExecutable, $settings->phpExecutable);
Assert::equal($expectedSettings->shortTag, $settings->shortTag);
Assert::equal($expectedSettings->aspTags, $settings->aspTags);
Assert::equal($expectedSettings->parallelJobs, $settings->parallelJobs);
Assert::equal($expectedSettings->extensions, $settings->extensions);
Assert::equal($expectedSettings->paths, $settings->paths);
Assert::equal($expectedSettings->excluded, $settings->excluded);
Assert::equal($expectedSettings->colors, $settings->colors);
