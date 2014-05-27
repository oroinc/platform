<?php

namespace Oro\Bundle\CronBundle\Job;

use Symfony\Component\Process\Process;

use Oro\Bundle\InstallerBundle\Process\PhpExecutableFinder;

class Daemon
{
    /**
     * Kernel root dir
     *
     * @var string
     */
    protected $rootDir;

    /**
     * Maximum number of concurrent jobs
     *
     * @var int
     */
    protected $maxJobs;

    /**
     * Current environment
     *
     * @var string
     */
    protected $env;

    /**
     * Path to php executable
     *
     * @var string
     */
    protected $phpExec;

    /**
     * @var int
     */
    protected $pid;

    /**
     *
     * @param string $rootDir
     * @param int    $maxJobs [optional] Maximum number of concurrent jobs. Default value is 5.
     * @param string $env     [optional] Environment. Default value is "prod".
     */
    public function __construct($rootDir, $maxJobs = 5, $env = 'prod')
    {
        $this->rootDir = rtrim($rootDir, DIRECTORY_SEPARATOR);
        $this->maxJobs = (int)$maxJobs;
        $this->env     = $env;
    }

    /**
     * Run daemon in background
     *
     * @param string $outputFile
     * @throws \RuntimeException
     * @return int|null The process id if running successfully, null otherwise
     */
    public function run($outputFile = '/dev/null')
    {
        if ($this->getPid()) {
            throw new \RuntimeException('Daemon process already started');
        }

        // workaround for Windows
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $wsh = new \COM('WScript.shell');

            $wsh->Run($this->getQueueRunCmd(), 0, false);

            return $this->getPid();
        }

        $this->pid = shell_exec(sprintf(
            '%s > %s 2>&1 & echo $!',
            $this->getQueueRunCmd(),
            $outputFile
        ));

        return $this->getPid();
    }

    /**
     * Stop daemon
     *
     * @throws \RuntimeException
     * @return bool              True if success, false otherwise
     */
    public function stop()
    {
        $pid = $this->getPid();

        if (!$pid) {
            throw new \RuntimeException('Daemon process not found');
        }

        $process = $this->getQueueStopProcess($pid);

        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Check if jobs queue daemon is running
     *
     * @return int|null Daemon process id on success, null otherwise
     */
    public function getPid()
    {
        if (!$this->pid) {
            $this->pid = $this->findProcessPid(
                sprintf('%sconsole jms-job-queue:run', $this->rootDir . DIRECTORY_SEPARATOR)
            );
        }

        return $this->pid;
    }

    /**
     * @param string $searchTerm
     * @return int|null
     */
    protected function findProcessPid($searchTerm)
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $cmd          = 'WMIC path win32_process get Processid,Commandline | findstr %s | findstr /V findstr';
            $searchRegExp = '/\s+(\d+)\s*$/Usm';
        } else {
            $cmd          = 'ps ax | grep %s | grep -v grep';
            $searchRegExp = '/^\s*(\d+)\s+/Usm';
        }
        $cmd = sprintf($cmd, escapeshellarg($searchTerm));

        $process = new Process($cmd);
        $process->run();
        $output = $process->getOutput();

        if (!empty($output)) {
            preg_match($searchRegExp, $output, $matches);
            if (count($matches) > 1 && !empty($matches[1])) {
                return (int)$matches[1];
            }
        }

        return null;
    }

    /**
     * Instantiate "kill" (*nix) / "taskkill" (Windows) command to terminate job queue
     *
     * @param  int $pid Process id to kill
     * @return Process
     */
    protected function getQueueStopProcess($pid)
    {
        $cmd = defined('PHP_WINDOWS_VERSION_BUILD') ? 'taskkill /F /PID %u' : 'kill %u';

        return new Process(sprintf($cmd, $pid));
    }

    /**
     * Get command line to run job queue
     *
     * @return string
     */
    protected function getQueueRunCmd()
    {
        if (!$this->phpExec) {
            $finder = new PhpExecutableFinder();

            $this->phpExec = escapeshellarg($finder->find());
        }

        $runCommand = sprintf(
            '%s %sconsole jms-job-queue:run --max-runtime=999999999 --max-concurrent-jobs=%u --env=%s',
            $this->phpExec,
            $this->rootDir . DIRECTORY_SEPARATOR,
            max($this->maxJobs, 1),
            escapeshellarg($this->env)
        );

        return $runCommand;
    }
}
