PHP Parallel Lint
=============

This tool check syntax of PHP files about 20x faster then serial check.

Running parallel jobs in PHP inspired by Nette framework tests.

Example output
---------------
```
a03-0524a:PHP-Parallel-Lint jakubonderka$ php run.php test.php test2.php
.X

Checked 2 files, syntax error found in 1 file

----------------------------------------------------------------------
Parse error: test2.php:40
    38|     $manager = new ParallelLint\Manager;
    39|     $setting = 2$manager->parseArguments($_SERVER['argv']);
  > 40|     $result = $manager->run($setting);
    41|     die($result ? SUCCESS : WITH_ERRORS);
    42| } catch (ParallelLint\InvalidArgumentException $e) {
Unexpected T_VARIABLE
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

