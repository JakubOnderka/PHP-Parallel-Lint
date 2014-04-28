<?php

require_once __DIR__ . '/../vendor/nette/tester/Tester/bootstrap.php';
require_once __DIR__ . '/../src/Error.php';
require_once __DIR__ . '/../src/Manager.php';
require_once __DIR__ . '/../src/Output.php';
require_once __DIR__ . '/../src/Process.php';
require_once __DIR__ . '/../src/Settings.php';
require_once __DIR__ . '/../src/exceptions.php';

use JakubOnderka\PhpParallelLint\Manager;
use JakubOnderka\PhpParallelLint\Settings;
use Tester\Assert;

class ManagerRunTest extends Tester\TestCase
{
    public function testBadPath()
    {
        $settings = $this->prepareSettings();
        $settings->paths = array('path/for-not-found/');
        $manager = new Manager($settings);
        Assert::exception(function() use ($manager, $settings) {
            $manager->run($settings);
        }, 'JakubOnderka\PhpParallelLint\NotExistsPathException');
    }



    public function testFilesNotFound()
    {
        $settings = $this->prepareSettings();
        $settings->paths = array('examples/example-01/');
        $manager = new Manager($settings);
        Assert::exception(function() use ($manager, $settings) {
            $manager->run($settings);
        }, 'JakubOnderka\PhpParallelLint\Exception', 'No file found to check.');
    }



    public function testSuccess()
    {
        $settings = $this->prepareSettings();
        $settings->paths = array('examples/example-02/');
        $manager = new Manager($settings);
        ob_start();
            $code = $manager->run($settings);
        ob_clean();
        Assert::true($code);
    }



    public function testError()
    {
        $settings = $this->prepareSettings();
        $settings->paths = array('examples/example-03/');
        $manager = new Manager($settings);
        ob_start();
            $code = $manager->run($settings);
        ob_clean();
        Assert::false($code);
    }



    /**
     * @return JakubOnderka\PhpParallelLint\Settings
     */
    private function prepareSettings()
    {
        $settings = new Settings();
        $settings->phpExecutable = 'php';
        $settings->shortTag = false;
        $settings->aspTags = false;
        $settings->parallelJobs = 10;
        $settings->extensions = array('php', 'phtml', 'php3', 'php4', 'php5');
        $settings->paths = array('FOR-SET');
        $settings->excluded = array();
        $settings->colors = false;

        return $settings;
    }
}

$testCase = new ManagerRunTest;
$testCase->run();
