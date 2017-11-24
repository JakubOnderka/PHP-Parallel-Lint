<?php

namespace JakubOnderka\PhpParallelLint;

/*
Copyright (c) 2014, Jakub Onderka
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those
of the authors and should not be interpreted as representing official policies,
either expressed or implied, of the FreeBSD Project.
 */

/**
 * Class Application
 * @package JakubOnderka\PhpParallelLint
 */
class Application
{
    const VERSION = '1.0.0';
    const SUCCESS = 0;
    const WITH_ERRORS = 1;
    const FAILED = 255;

    /**
     * Run the application
     */
    public function run()
    {
        if (in_array('proc_open', explode(',', ini_get('disable_functions')))) {
            echo "Function 'proc_open' is required, but it is disabled by disable_functions setting.", PHP_EOL;
            die(self::FAILED);
        }
        if (in_array('-h', $_SERVER['argv']) || in_array('--help', $_SERVER['argv'])) {
            $this->showUsage();
        }
        if (in_array('-V', $_SERVER['argv']) || in_array('--version', $_SERVER['argv'])) {
            $this->showVersion();
            die();
        }
        try {
            $settings = Settings::parseArguments($_SERVER['argv']);
            if ($settings->stdin) {
                $settings->addPaths(Settings::getPathsFromStdIn());
            }
            if (empty($settings->paths)) {
                $this->showUsage();
            }
            $manager = new Manager;
            $result = $manager->run($settings);
            if ($settings->ignoreFails) {
                die($result->hasSyntaxError() ? self::WITH_ERRORS : self::SUCCESS);
            } else {
                die($result->hasError() ? self::WITH_ERRORS : self::SUCCESS);
            }
        } catch (InvalidArgumentException $e) {
            echo "Invalid option {$e->getArgument()}", PHP_EOL, PHP_EOL;
            $this->showOptions();
            die(self::FAILED);
        } catch (Exception $e) {
            if (isset($settings) && $settings->format === Settings::FORMAT_JSON) {
                echo json_encode($e);
            } else {
                echo $e->getMessage(), PHP_EOL;
            }
            die(self::FAILED);
        } catch (Exception $e) {
            echo $e->getMessage(), PHP_EOL;
            die(self::FAILED);
        }
    }

    /**
     * Outputs the options
     */
    private function showOptions()
    {
        echo <<<HELP
Options:
    -p <php>        Specify PHP-CGI executable to run (default: 'php').
    -s, --short     Set short_open_tag to On (default: Off).
    -a, -asp        Set asp_tags to On (default: Off).
    -e <ext>        Check only files with selected extensions separated by comma.
                    (default: php,php3,php4,php5,phtml,phpt)
    --exclude       Exclude a file or directory. If you want exclude multiple items,
                    use multiple exclude parameters.
    -j <num>        Run <num> jobs in parallel (default: 10).
    --colors        Enable colors in console output. (disables auto detection of color support)
    --no-colors     Disable colors in console output.
    --no-progress   Disable progress in console output.
    --json          Output results as JSON string.
    --checkstyle    Output results as Checkstyle XML.
    --blame         Try to show git blame for row with error.
    --git <git>     Path to Git executable to show blame message (default: 'git').
    --stdin         Load files and folder to test from standard input.
    --ignore-fails  Ignore failed tests.
    -h, --help      Print this help.
    -V, --version   Display this application version

HELP;
    }

    /**
     * Outputs the current version
     */
    private function showVersion()
    {
        echo 'PHP Parallel Lint version ' . self::VERSION.PHP_EOL;
    }

    /**
     * Shows usage
     */
    private function showUsage()
    {
        $this->showVersion();
        echo <<<USAGE
-------------------------------
Usage:
parallel-lint [sa] [-p php] [-e ext] [-j num] [--exclude dir] [files or directories]

USAGE;
        $this->showOptions();
        die();
    }
}
