# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Added

- Allow for multi-part file extensions to be passed using -e (like `-e php,php.dist`) from [@jrfnl](https://github.com/jrfnl).
- Added syntax error callback [#30](https://github.com/php-parallel-lint/PHP-Parallel-Lint/pull/30) from [@arxeiss](https://github.com/arxeiss).
- Ignore PHP startup errors [#34](https://github.com/php-parallel-lint/PHP-Parallel-Lint/pull/34) from [@jrfnl](https://github.com/jrfnl).

### Internal

- Normalized composer.json from [@OndraM](https://github.com/OndraM).
- Updated PHPCS dependency from [@jrfnl](https://github.com/jrfnl).
- Cleaned coding style from [@jrfnl](https://github.com/jrfnl).
- Provide one true way to run the test suite [#37](https://github.com/php-parallel-lint/PHP-Parallel-Lint/pull/37) from [@mfn](https://github.com/mfn).

## [1.2.0] - 2020-04-04

### Added

- Added changelog.

### Fixed

- Fixed vendor location for running from other folder from [@Erkens](https://github.com/Erkens).

### Internal

- Added a .gitattributes file from [@jrfnl](https://github.com/jrfnl), thanks for issue to [@ondrejmirtes](https://github.com/ondrejmirtes).
- Fixed incorrect unit tests from [@jrfnl](https://github.com/jrfnl).
- Fixed minor grammatical errors from [@jrfnl](https://github.com/jrfnl).
- Added Travis: test against nightly (= PHP 8) from [@jrfnl](https://github.com/jrfnl).
- Travis: removed sudo from [@jrfnl](https://github.com/jrfnl).
- Added info about installing like not a dependency.
- Cleaned readme - new organization from previous package.
- Added checklist for new version from [@szepeviktor](https://github.com/szepeviktor).
