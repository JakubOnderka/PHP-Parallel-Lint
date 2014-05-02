<?php

require_once __DIR__ . '/../vendor/nette/tester/Tester/bootstrap.php';
require_once __DIR__ . '/../src/Error.php';
require_once __DIR__ . '/../src/Manager.php';
require_once __DIR__ . '/../src/Output.php';
require_once __DIR__ . '/../src/Process.php';
require_once __DIR__ . '/../src/Settings.php';
require_once __DIR__ . '/../src/exceptions.php';

use JakubOnderka\PhpParallelLint\Manager;
use JakubOnderka\PhpParallelLint\NullWriter;
use JakubOnderka\PhpParallelLint\Output;
use JakubOnderka\PhpParallelLint\Settings;
use Tester\Assert;

class ManagerRunTest extends Tester\TestCase
{
    public function testBadPath()
    {
        $settings = $this->prepareSettings();
        $settings->paths = array('path/for-not-found/');
        $manager = $this->getManager($settings);
        Assert::exception(function() use ($manager, $settings) {
            $manager->run($settings);
        }, 'JakubOnderka\PhpParallelLint\NotExistsPathException');
    }

    public function testFilesNotFound()
    {
        $settings = $this->prepareSettings();
        $settings->paths = array('examples/example-01/');
        $manager = $this->getManager($settings);
        Assert::exception(function() use ($manager, $settings) {
            $manager->run($settings);
        }, 'JakubOnderka\PhpParallelLint\Exception', 'No file found to check.');
    }

    public function testSuccess()
    {
        $settings = $this->prepareSettings();
        $settings->paths = array('examples/example-02/');

        $manager = $this->getManager($settings);
        $code = $manager->run($settings);
        Assert::true($code);
    }

    public function testError()
    {
        $settings = $this->prepareSettings();
        $settings->paths = array('examples/example-03/');

        $manager = $this->getManager($settings);
        $code = $manager->run($settings);
        Assert::false($code);
    }

    public function testExcludeRelativeSubdirectory()
    {
        $settings = $this->prepareSettings();
        $settings->paths = array('examples/example-04/');

        $manager = $this->getManager($settings);
        $code = $manager->run($settings);
        Assert::false($code);

        $settings->excluded = array('examples/example-04/dir1/dir2');

        $manager = $this->getManager($settings);
        $code = $manager->run($settings);
        Assert::true($code);
    }

    public function testExcludeAbsoluteSubdirectory()
    {
        $settings = $this->prepareSettings();
        $cwd = getcwd();
        $settings->paths = array($cwd . '/examples/example-04/');
        $settings->excluded = array();

        $manager = $this->getManager($settings);
        $code = $manager->run($settings);
        Assert::false($code);

        $settings->excluded = array($cwd . '/examples/example-04/dir1/dir2');

        $manager = $this->getManager($settings);
        $code = $manager->run($settings);
        Assert::true($code);
    }

    /**
     * @param Settings $settings
     * @return Manager
     */
    private function getManager(Settings $settings)
    {
        $manager = new Manager($settings);
        $manager->setOutput(new Output(new NullWriter()));
        return $manager;
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
