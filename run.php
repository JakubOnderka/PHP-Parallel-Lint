<?php
if (PHP_VERSION < '5.3') {
    die("PHP Parallel Lint require PHP 5.3 and newer");
}

const SUCCESS = 0,
    WITH_ERRORS = 1,
    FAILED = 255;

function showOptions()
{
?>
Options:
    -p <php>    Specify PHP-CGI executable to run.
    -short      Set short_open_tag to On (default Off)
    -asp        Set asp_tags to On (default Off)
    -e <ext>    Check only files with selected extension separated by comma (default: php,php3,php4,php5,phtml)
    -j <num>    Run <num> jobs in parallel (default 10)
    -h, --help  Print this help.
<?php
}

/**
 * Help
 */
if (!isset($_SERVER['argv'][1]) || in_array('-h', $_SERVER['argv']) || in_array('--help', $_SERVER['argv'])) { ?>
PHP Parallel Lint version 0.1
---------------------------
Usage:
	php run.php [options] [files or directories separated by space]
<?php
    showOptions();
    exit;
}

require_once __DIR__ . '/src/Manager.php';

try {
    $manager = new ParallelLint\Manager;
    $setting = $manager->parseArguments($_SERVER['argv']);
    $result = $manager->run($setting);
    die($result ? SUCCESS : WITH_ERRORS);
} catch (ParallelLint\InvalidArgumentException $e) {
    echo "Invalid option {$e->getArgument()}" . PHP_EOL . PHP_EOL;
    showOptions();
    die(FAILED);
} catch (Exception $e) {
    echo $e->getMessage(), PHP_EOL;
    die(FAILED);
}