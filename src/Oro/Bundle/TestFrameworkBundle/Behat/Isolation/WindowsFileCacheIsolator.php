<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class WindowsFileCacheIsolator extends OsRelatedIsolator implements IsolatorInterface
{
    /** @var string */
    protected $cacheDir;

    /** @var  string */
    protected $cacheDump;

    /** @var  string */
    protected $cacheTempDir;

    /** @var  Process */
    protected $process;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->cacheDir     = realpath($kernel->getCacheDir());
        $this->cacheDump    = sys_get_temp_dir().'\oro_cache_dump';
        $this->cacheTempDir = substr($this->cacheDir, 0, -1).'~';
    }

    /** {@inheritdoc} */
    public function start()
    {
        $this->runProcess(sprintf(
            'xcopy %s %s /E /R /H /I /K /Y', $this->cacheDir, $this->cacheDump
        ));
        $this->startProcess(sprintf(
            'xcopy %s %s /E /R /H /I /K /Y', $this->cacheDump, $this->cacheTempDir
        ));
    }

    /** {@inheritdoc} */
    public function beforeTest()
    {}

    /** {@inheritdoc} */
    public function afterTest()
    {
        $this->runProcess(sprintf(
            'rd /s /q %s', $this->cacheDir
        ));
        while ($this->process->isRunning()) {
            // waiting for process to finish or fail by timeout
        }
        $this->runProcess(sprintf(
            'cd %s & ren %s %s',
            realpath($this->cacheDir.'\..'),
            basename($this->cacheTempDir),
            basename($this->cacheDir)
        ));
        $this->startProcess(sprintf(
            'xcopy %s %s /E /R /H /I /K /Y', $this->cacheDump, $this->cacheTempDir
        ));
    }

    /** {@inheritdoc} */
    public function terminate()
    {
        $this->process->stop();
        $this->runProcess(
            sprintf('rd /s /q %s & rd /s /q %s', $this->cacheDump, $this->cacheTempDir)
        );
    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return
            $this->isApplicableOS()
            && 'session.handler.native_file' == $container->getParameter('session_handler');
    }

    /** {@inheritdoc} */
    protected function getApplicableOs()
    {
        return [
            OsRelatedIsolator::WINDOWS_OS,
        ];
    }

    /**
     * @param string $commandline The command line to run
     * @param int $timeout The timeout in seconds
     */
    protected function runProcess($commandline, $timeout = 120)
    {
        $this->process = new Process($commandline);

        $this->process
            ->setTimeout($timeout)
            ->run();

        if (!$this->process->isSuccessful()) {
            throw new ProcessFailedException($this->process);
        }
    }

    protected function startProcess($commandline, $timeout = 120)
    {
        $this->process = new Process($commandline);
        $this->process
            ->setTimeout($timeout)
            ->start();
    }
}
