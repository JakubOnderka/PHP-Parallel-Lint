PHP Parallel Lint
=============

This tool check syntax of PHP files about 20x faster then serial check.

Running parallel jobs in PHP inspired by Nette framework tests.

Using in ANT
---------------
```xml
<target name="lint" description="Check syntax errors in PHP files">
    <exec executable="php">
        <arg line="${basedir}/tests/lint/run.php" />
        <arg line="${basedir}/private/" />
    </exec>
</target>
```