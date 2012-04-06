<?php
namespace ParallelLint;

class Process
{
    /** @var resource */
    protected $process;

    /** @var resource */
    protected $stdout;

    /** @var string */
    protected $output;

    public function __construct($cmdLine, $blocking = false)
    {
        $descriptors = array(
			array('pipe', 'r'),
			array('pipe', 'w'),
			array('pipe', 'w'),
		);

        $this->process = proc_open($cmdLine, $descriptors, $pipes, null, null, array('bypass_shell' => true));

        list($stdin, $this->stdout, $stderr) = $pipes;
        fclose($stdin);
        //stream_set_blocking($this->stdout, $blocking ? 1 : 0);
        fclose($stderr);
    }

    public function isReady()
    {
        $status = proc_get_status($this->process);
        return !$status['running'];
    }

    public function getResults()
    {
        $this->output = stream_get_contents($this->stdout);
        fclose($this->stdout);

        return proc_close($this->process);
    }

    public function getOutput()
    {
        return $this->output;
    }
}

class LintProcess extends Process
{
    /**
     * @return bool
     */
    public function hasSyntaxError()
    {
        return strpos($this->output, 'No syntax errors detected') === false;
    }

    /**
     * @return bool|string
     */
    public function getSyntaxError()
    {
        if ($this->hasSyntaxError()) {
            list(, $out) = explode("\n", $this->output);
            return $out;
        }

        return false;
    }
}