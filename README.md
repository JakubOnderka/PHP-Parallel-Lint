PHP Parallel Lint
=============

This tool check syntax of PHP files about 20x faster then serial check.

Running parallel jobs in PHP inspired by Nette framework tests.


Install
-------

Just create a `composer.json` file and run the `php composer.phar install` command to install it:

```
{
    "require-dev": {
        "jakub-onderka/php-parallel-lint": "dev-master"
    }
}
```


Example output
---------------
```
a03-0524a:PHP-Parallel-Lint jakubonderka$ php run.php .
X.......

Checked 8 files in 0.1 second, syntax error found in 1 file

----------------------------------------------------------------------
Parse error: ./error.php:40
    38| try {
    39|     $manager = new ParallelLint\Manager;
  > 40|     $setting = $manager->->parseArguments($_SERVER['argv']);
    41|     $result = $manager->run($setting);
    42|     die($result ? SUCCESS : WITH_ERRORS);
Unexpected T_OBJECT_OPERATOR (->), expecting T_STRING or T_VARIABLE or '{' or '$'
```


Using in ANT
---------------
```xml
<target name="lint" description="Check syntax errors in PHP files">
    <exec executable="php" failonerror="true">
        <arg line="${basedir}/tests/lint/run.php" />
        <arg line="${basedir}/private/" />
    </exec>
</target>
```
