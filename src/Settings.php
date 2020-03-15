<?php
namespace JakubOnderka\PhpParallelLint;

class Settings
{

    /**
     * constants for enum settings
     */
    const FORCED = 'FORCED';
    const DISABLED = 'DISABLED';
    const AUTODETECT = 'AUTODETECT';

    const FORMAT_TEXT = 'text';
    const FORMAT_JSON = 'json';
    const FORMAT_CHECKSTYLE = 'checkstyle';

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
     * Number of jobs running in same time
     * @var int
     */
    public $parallelJobs = 10;

    /**
     * If path contains directory, only file with these extensions are checked
     * @var array
     */
    public $extensions = array('php', 'phtml', 'php3', 'php4', 'php5', 'phpt');

    /**
     * Array of file or directories to check
     * @var array
     */
    public $paths = array();

    /**
     * Don't check files or directories
     * @var array
     */
    public $excluded = array();

    /**
     * Mode for color detection. Possible values: self::FORCED, self::DISABLED and self::AUTODETECT
     * @var string
     */
    public $colors = self::AUTODETECT;

    /**
     * Show progress in text output
     * @var bool
     */
    public $showProgress = true;

    /**
     * Output format (see FORMAT_* constants)
     * @var string
     */
    public $format = self::FORMAT_TEXT;

    /**
     * Read files and folder to tests from standard input (blocking)
     * @var bool
     */
    public $stdin = false;

    /**
     * Try to show git blame for row with error
     * @var bool
     */
    public $blame = false;

    /**
     * Path to git executable for blame
     * @var string
     */
    public $gitExecutable = 'git';

    /**
     * @var bool
     */
    public $ignoreFails = false;

    /**
     * @var bool
     */
    public $showDeprecated = false;

    /**
     * @param array $paths
     */
    public function addPaths(array $paths)
    {
        $this->paths = array_merge($this->paths, $paths);
    }

    /**
     * @param array $arguments
     * @return Settings
     * @throws InvalidArgumentException
     */
    public static function parseArguments(array $arguments)
    {
        $arguments = new ArrayIterator(array_slice($arguments, 1));
        $settings = new self;

        // Use the currently invoked php as the default if possible
        if (defined('PHP_BINARY')) {
            $settings->phpExecutable = PHP_BINARY;
        }

        foreach ($arguments as $argument) {
            if ($argument{0} !== '-') {
                $settings->paths[] = $argument;
            } else {
                switch ($argument) {
                    case '-p':
                        $settings->phpExecutable = $arguments->getNext();
                        break;

                    case '-s':
                    case '--short':
                        $settings->shortTag = true;
                        break;

                    case '-a':
                    case '--asp':
                        $settings->aspTags = true;
                        break;

                    case '--exclude':
                        $settings->excluded[] = $arguments->getNext();
                        break;

                    case '-e':
                        $settings->extensions = array_map('trim', explode(',', $arguments->getNext()));
                        break;

                    case '-j':
                        $parallelJobs = $arguments->getNext();
                        if ($parallelJobs === 'auto' && $cpuNumber = static::getNumberOfCPUCores()) {
                            $settings->parallelJobs = $cpuNumber;
                            break;
                        }
                        $settings->parallelJobs = max((int) $parallelJobs, 1);
                        break;

                    case '--colors':
                        $settings->colors = self::FORCED;
                        break;

                    case '--no-colors':
                        $settings->colors = self::DISABLED;
                        break;

                    case '--no-progress':
                        $settings->showProgress = false;
                        break;

                    case '--json':
                        $settings->format = self::FORMAT_JSON;
                        break;

                    case '--checkstyle':
                        $settings->format = self::FORMAT_CHECKSTYLE;
                        break;

                    case '--git':
                        $settings->gitExecutable = $arguments->getNext();
                        break;

                    case '--stdin':
                        $settings->stdin = true;
                        break;

                    case '--blame':
                        $settings->blame = true;
                        break;

                    case '--ignore-fails':
                        $settings->ignoreFails = true;
                        break;

                    case '--show-deprecated':
                        $settings->showDeprecated = true;
                        break;

                    default:
                        throw new InvalidArgumentException($argument);
                }
            }
        }

        return $settings;
    }

    /**
     * @return array
     */
    public static function getPathsFromStdIn()
    {
        $content = stream_get_contents(STDIN);

        if (empty($content)) {
            return array();
        }

        $lines = explode("\n", rtrim($content));
        return array_map('rtrim', $lines);
    }


    /**
     * Return number of (logical) CPU cores, or null (if couldn't extract such info).
     *
     * Copied from https://github.com/paratestphp/paratest/blob/1dc09c5457df8fc4bae4fbbdcba3cef22f2d834c/src/Runners/PHPUnit/Options.php#L382-L411
     *
     * @return int|null
     */
    private static function getNumberOfCPUCores()
    {
        $cores = 2;
        if (is_file('/proc/cpuinfo')) {
            // Linux (and potentially Windows with linux sub systems)
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            $cores = \count($matches[0]);
        } elseif (\DIRECTORY_SEPARATOR === '\\') {
            // Windows
            if (($process = @popen('wmic cpu get NumberOfCores', 'rb')) !== false) {
                fgets($process);
                $cores = (int) fgets($process);
                pclose($process);
            }
        } elseif (($process = @popen('sysctl -n hw.ncpu', 'rb')) !== false) {
            // *nix (Linux, BSD and Mac)
            $cores = (int) fgets($process);
            pclose($process);
        }

        return $cores;
    }
}

class ArrayIterator extends \ArrayIterator
{
    public function getNext()
    {
        $this->next();
        return $this->current();
    }
}
