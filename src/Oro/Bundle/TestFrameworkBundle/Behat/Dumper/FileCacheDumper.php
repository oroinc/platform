<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Dumper;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class FileCacheDumper implements CacheDumperInterface
{
    /** The max runtime for a process in seconds */
    const TIMEOUT = 30;

    /** @var string */
    protected $cacheDir;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->cacheDir = $kernel->getCacheDir();
    }

    /**
     * {@inheritdoc}
     */
    public function dump()
    {
        $this->runProcess(sprintf(
            'tar -cf cache.tar -C %s .',
            $this->cacheDir
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function restore()
    {
        $this->runProcess(sprintf(
            'rm -rf %s && mkdir %1$s',
            $this->cacheDir
        ));
        $this->runProcess(sprintf(
            'tar -xf cache.tar -C %s .',
            $this->cacheDir
        ));

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
