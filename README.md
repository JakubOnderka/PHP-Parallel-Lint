PHP Parallel Lint
=================

This tool check syntax of PHP files faster then serial check with fancier output.

Running parallel jobs in PHP inspired by Nette framework tests.


Install
-------

Just create a `composer.json` file and run the `php composer.phar install` command to install it:

```json
{
    "require-dev": {
        "jakub-onderka/php-parallel-lint": "dev-master"
    }
}
```

For colored output install suggested package `jakub-onderka/php-console-highlighter`.


Example output
--------------

```
$ ./parallel-lint .
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

Recommended setting for usage with Symfony framework
--------------

For run from command line:

```
$ ./bin/parallel-lint --exclude app --exclude vendor .
```

or setting for ANT:

```xml
<condition property="parallel-lint" value="${basedir}/bin/parallel-lint.bat" else="${basedir}/bin/parallel-lint">
    <os family="windows"/>
</condition>

<target name="parallel-lint" description="Run PHP parallel lint">
    <exec executable="${parallel-lint}" failonerror="true">
        <arg line='--exclude ${basedir}/app/' />
        <arg line='--exclude ${basedir}/vendor/' />
        <arg line='${basedir}' />
    </exec>
</target>
```
