<?php

$file = end($_SERVER['argv']);
if ($file === $_SERVER['PHP_SELF'] || !file_exists($file)) {
	exit(1);
}

$f = fopen($file, 'r');
$firstLine = fgets($f);
@fclose($f);

if (!preg_match('~<?php\\s*\\/\\/\s*lint\s*([^\d\s]+)\s*([^\s]+)\s*~i', $firstLine, $m)) {
	exit(0);
}

exit(isset($m[2]) && version_compare(PHP_VERSION, $m[2], $m[1]) != 1 ? 2 : 0);
