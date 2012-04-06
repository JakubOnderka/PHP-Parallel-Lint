<?php
if (PHP_VERSION < '5.3') {
    die("PHP Parallel Lint require PHP 5.3 and newer");
}

const SUCCESS = 0,
    WITH_ERRORS = 1,
    FAILED = 255;

function showOptions() {
?>
Options:
	-p <php>    Specify PHP-CGI executable to run.
	-log <path> Write log to file <path>.
    -short      Set short_open_tag to On
    -asp        Set asp_tags to On
    -e <ext>    Check file with extension separated by space (default: php, php3, phtml)
	-j <num>    Run <num> jobs in parallel.
<?php
}

/**
 * Help
 */
if (!isset($_SERVER['argv'][1])) { ?>
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
    echo $e->getMessage();
    die(FAILED);
}