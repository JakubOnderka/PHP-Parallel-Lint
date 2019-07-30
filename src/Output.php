<?php

namespace JakubOnderka\PhpParallelLint;

interface Output
{
    public function __construct(IWriter $writer);

    public function ok();

    public function skip();

    public function error();

    public function fail();

    public function setTotalFileCount($count);

    public function writeHeader($phpVersion, $parallelJobs, $hhvmVersion = null);

    public function writeResult(Result $result, ErrorFormatter $errorFormatter, $ignoreFails);
}
