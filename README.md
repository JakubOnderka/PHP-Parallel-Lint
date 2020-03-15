# PHP Parallel Lint

**This repository is abandoned.**

Suggested alternative: https://github.com/php-parallel-lint/PHP-Parallel-Lint

-----

This tool checks syntax of PHP files faster than serial check with a fancier output.

Running parallel jobs in PHP is inspired by Nette framework tests.

## Installation

Just run the following command to install it:

    composer require --dev jakub-onderka/php-parallel-lint

For colored output also install the suggested package `jakub-onderka/php-console-highlighter`:

    composer require --dev jakub-onderka/php-console-highlighter

## Example output

![Example use of tool with error](/tests/examples/example-images/use-error.png?raw=true "Example use of tool with error")


## Options for run

- `-p <php>`        Specify PHP-CGI executable to run (default: the php binary used to run parallel-lint).
- `-s, --short`     Set short_open_tag to On (default: Off).
- `-a, --asp`        Set asp_tags to On (default: Off).
- `-e <ext>`        Check only files with selected extensions separated by comma. (default: php,php3,php4,php5,phtml,phpt)
- `--exclude`       Exclude a file or directory. If you want exclude multiple items, use multiple exclude parameters.
- `-j <num>`        Run <num> jobs in parallel (default: 10).
- `--colors`        Force enable colors in console output.
- `--no-colors`     Disable colors in console output.
- `--no-progress`   Disable progress in console output.
- `--checkstyle`    Output results as Checkstyle XML.
- `--json`          Output results as JSON string (require PHP 5.4).
- `--blame`         Try to show git blame for row with error.
- `--git <git>`     Path to Git executable to show blame message (default: 'git').
- `--stdin`         Load files and folder to test from standard input.
- `--ignore-fails`  Ignore failed tests.
- `-h, --help`      Print this help.
- `-V, --version`   Display this application version.


## Recommended setting for usage with Symfony framework

For run from command line:

    vendor/bin/parallel-lint --exclude app --exclude vendor .

## Create Phar package

PHP Parallel Lint supports [Box app](https://box-project.github.io/box2/) for creating Phar package. First, install box app:


    curl -LSs https://box-project.github.io/box2/installer.php | php


and then run this command in parallel lint folder, which creates `parallel-lint.phar` file.


    box build


------

[![Downloads this Month](https://img.shields.io/packagist/dm/jakub-onderka/php-parallel-lint.svg)](https://packagist.org/packages/jakub-onderka/php-parallel-lint)
[![Build Status](https://travis-ci.org/JakubOnderka/PHP-Parallel-Lint.svg?branch=master)](https://travis-ci.org/JakubOnderka/PHP-Parallel-Lint)
[![Build status](https://ci.appveyor.com/api/projects/status/5n3qqa3r66aoghjo/branch/master?svg=true)](https://ci.appveyor.com/project/JakubOnderka/php-parallel-lint/branch/master)
[![License](https://poser.pugx.org/jakub-onderka/php-parallel-lint/license.svg)](https://packagist.org/packages/jakub-onderka/php-parallel-lint)
