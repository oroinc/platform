<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

trait AbstractDbIsolator
{
    /** @var string */
    protected $dbHost;

    /** @var  string */
    protected $dbPort;

    /** @var string */
    protected $dbName;

    /** @var string */
    protected $dbPass;

    /** @var string */
    protected $dbUser;

    /** @var string */
    protected $cacheDir;

    /**
     * @var string full path to DB dump file
     */
    protected $dbDump;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $kernel->boot();
        $container = $kernel->getContainer();

        $this->cacheDir = $kernel->getCacheDir();
        $this->dbHost = $container->getParameter('database_host');
        $this->dbPort = $container->getParameter('database_port');
        $this->dbName = $container->getParameter('database_name');
        $this->dbUser = $container->getParameter('database_user');
        $this->dbPass = $container->getParameter('database_password');
        $this->dbDump = sys_get_temp_dir().DIRECTORY_SEPARATOR.$this->dbName;
    }

    /**
     * @param string $commandline The command line to run
     * @param int $timeout The timeout in seconds
     */
    protected function runProcess($commandline, $timeout = 120)
    {
        $process = new Process($commandline);

        $process->setTimeout($timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
