<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

abstract class AbstractFileCacheOsRelatedIsolator extends AbstractOsRelatedIsolator implements IsolatorInterface
{
    const TIMEOUT = 240;

    /** @var  string */
    protected $cacheDir;

    /** @var  string */
    protected $cacheDumpDir;

    /** @var  string */
    protected $cacheTempDir;

    /** @var  Process */
    protected $copyDumpToTempDirProcess;

    /** @param KernelInterface $kernel */
    public function __construct(KernelInterface $kernel)
    {
        $this->cacheDir     = realpath($kernel->getCacheDir());
        $this->cacheTempDir = $this->cacheDir.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Temp';
        $this->cacheDumpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'oro_application_cache_dump_'.
            TokenGenerator::generateToken('cache');
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
        $event->writeln('<info>Create temp directory</info>');
        $this->cacheTempDir = $this->createCacheTempDirectory();
        $event->writeln('<info>Dumping cache directories</info>');
        $this->createCacheDumpDirectory();
        $this->dumpCache();
        $event->writeln('<info>Start process for copying Dump to Temp cache directory</info>');
        $this->startCopyDumpToTempDir();
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        if (!$this->copyDumpToTempDirProcess) {
            return;
        }

        $this->waitForProcess();
        $event->writeln('<info>Restore cache dirs</info>');
        $this->removeCacheDirs();
        $this->replaceCache();
        $this->startCopyDumpToTempDir();
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
        if (!$this->copyDumpToTempDirProcess) {
            return;
        }

        $event->writeln('<info>Stop copying cache</info>');
        $this->copyDumpToTempDirProcess->stop();
        $event->writeln('<info>Remove Temp cache dir</info>');
        $this->removeTempCacheDir();
        $event->writeln('<info>Remove Dump cache dir</info>');
        $this->removeDumpCacheDir();
    }

    /** {@inheritdoc} */
    public function restoreState(RestoreStateEvent $event)
    {
        $event->writeln('<info>Begin to restore the state Cache...</info>');

        if (false === is_dir($this->cacheDumpDir)) {
            throw new RuntimeException('Can\'t restore cache without dump');
        }

        $this->removeCacheDirs();
        $this->removeTempCacheDir();
        $this->cacheTempDir = $this->createCacheTempDirectory();
        $this->startCopyDumpToTempDir();
        $this->waitForProcess();
        $this->replaceCache();
        $event->writeln('<info>Cache state was restored</info>');
    }

    /**
     * {@inheritdoc}
     */
    public function isOutdatedState()
    {
        if (is_dir($this->cacheDumpDir)) {
            return true;
        }

        return false;
    }

    public function getTag()
    {
        return 'cache';
    }

    /**
     * @param string $commandline The command line to run
     */
    protected function runProcess($commandline)
    {
        $process = new Process($commandline);

        $process
            ->setTimeout(self::TIMEOUT)
            ->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    protected function waitForProcess()
    {
        while ($this->copyDumpToTempDirProcess->isRunning()) {
            // waiting for process to finish or fail by timeout
        }

        if (!$this->copyDumpToTempDirProcess->isSuccessful()) {
            throw new ProcessFailedException($this->copyDumpToTempDirProcess);
        }
    }

    /**
     * @return string
     */
    protected function createCacheDumpDirectory()
    {
        false === is_dir($this->cacheDumpDir) ?: $this->removeDumpCacheDir();
        mkdir($this->cacheDumpDir);

        return realpath($this->cacheDumpDir);
    }

    /**
     * @return string
     */
    protected function createCacheTempDirectory()
    {
        false === is_dir($this->cacheTempDir) ?: $this->removeTempCacheDir();
        mkdir($this->cacheTempDir);

        return realpath($this->cacheTempDir);
    }

    abstract protected function replaceCache();

    abstract protected function startCopyDumpToTempDir();

    abstract protected function dumpCache();

    abstract protected function removeDumpCacheDir();

    abstract protected function removeTempCacheDir();

    abstract protected function removeCacheDirs();
}
