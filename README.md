# PHP Parallel Lint

[![Downloads this Month](https://img.shields.io/packagist/dm/php-parallel-lint/php-parallel-lint.svg)](https://packagist.org/packages/php-parallel-lint/php-parallel-lint)
[![Build Status](https://travis-ci.org/php-parallel-lint/PHP-Parallel-Lint.svg?branch=master)](https://travis-ci.org/php-parallel-lint/PHP-Parallel-Lint)
[![License](https://poser.pugx.org/php-parallel-lint/php-parallel-lint/license.svg)](https://packagist.org/packages/php-parallel-lint/php-parallel-lint)

This tool checks syntax of PHP files faster than serial check with a fancier output.
Running parallel jobs in PHP is inspired by Nette framework tests.

This works from PHP 5.4 to 7.4

## Table of contents

1. [Installation](#installation)
2. [Example output](#example-output)
3. [Fork](#fork)
4. [Options for run](#options-for-run)
5. [Options for Symfony](#recommended-setting-for-usage-with-symfony-framework)
6. [Create Phar package](#create-phar-package)
7. [How upgrade](#how-upgrade)

## Installation

Just run the following command to install it:

    composer require --dev php-parallel-lint/php-parallel-lint

When you cannot use tool as dependency then you can install as project. Command for it:

    composer create-project php-parallel-lint/php-parallel-lint /path/to/folder/php-parallel-lint
    /path/to/folder/php-parallel-lint/parallel-lint # running tool

For colored output also install the suggested package `php-parallel-lint/php-console-highlighter`:

    composer require --dev php-parallel-lint/php-console-highlighter

## Example output

![Example use of tool with error](/tests/examples/example-images/use-error.png?raw=true "Example use of tool with error")


## Fork
This is a fork of [original project](https://github.com/JakubOnderka/PHP-Parallel-Lint). Why I forked it and why I am the right man?

- Project is used in many and projects.
- I am [second most active](https://github.com/JakubOnderka/PHP-Parallel-Lint/graphs/contributors) contributor in original project.
- Author does [not responds to issues and PRs](https://github.com/JakubOnderka/PHP-Parallel-Lint/pulls) and my mail messages.

## Options for run

- `-p <php>`        Specify PHP-CGI executable to run (default: 'php').
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

## How upgrade

Are you using original package? You can easy use this fork. Steps for upgrade are:

    composer remove --dev jakub-onderka/php-parallel-lint
    composer require --dev php-parallel-lint/php-parallel-lint
