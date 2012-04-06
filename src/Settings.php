<?php
namespace ParallelLint;

class Settings
{
    /**
     * Path to PHP executable
     * @var string
     */
    public $phpExecutable = 'php';

    /**
     * Check code inside PHP opening short tag <? or <?= in PHP 5.3
     * @var bool
     */
    public $shortTag = false;

    /**
     * Check PHP code inside ASP-style <% %> tags.
     * @var bool
     */
    public $aspTags = false;

    /**
     * Path to log file. If null, no log file is created
     * @var string
     */
    public $logFile;

    /**
     * Number of jobs running in same time
     * @var int
     */
    public $parallelJobs = 10;

    /**
     * If path contains directory, only file with these extensions are checked
     * @var array
     */
    public $extensions = array('php', 'phtml', 'php3');

    /**
     * Array of file or directories to check
     * @var array
     */
    public $paths = array();
}