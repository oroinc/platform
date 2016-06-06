<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Dumper;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class AbstractDbDumper implements DumperInterface
{
    /** The max runtime for a process in seconds */
    const TIMEOUT = 30;

    /** @var string */
    protected $dbHost;

    /** @var string */
    protected $dbName;

    /** @var string */
    protected $dbPass;

    /** @var string */
    protected $dbUser;

    /** @var string */
    protected $cacheDir;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $kernel->boot();
        $container = $kernel->getContainer();

        $this->cacheDir = $kernel->getCacheDir();
        $this->dbHost = $container->getParameter('database_host');
        $this->dbName = $container->getParameter('database_name');
        $this->dbUser = $container->getParameter('database_user');
        $this->dbPass = $container->getParameter('database_password');
    }

    /**
     * @param string $commandline The command line to run
     */
    protected function runProcess($commandline)
    {
        $process = new Process($commandline);

        $process->setTimeout(static::TIMEOUT);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
