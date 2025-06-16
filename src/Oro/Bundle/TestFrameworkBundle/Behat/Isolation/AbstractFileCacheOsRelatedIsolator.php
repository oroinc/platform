<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Abstraction for file cache isolator.
 */
abstract class AbstractFileCacheOsRelatedIsolator extends AbstractOsRelatedIsolator implements IsolatorInterface
{
    const int TIMEOUT = 240;

    protected array $cacheDirectories;
    protected string $cacheDir;
    protected string $cacheDumpDir;
    protected string $cacheTempDir;
    protected ?Process $copyDumpToTempDirProcess = null;

    public function __construct(
        KernelInterface $kernel,
        array $cacheDirectories,
    ) {
        $this->cacheDir = realpath($kernel->getCacheDir());
        $this->cacheTempDir = $this->cacheDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Temp';
        $this->cacheDumpDir = $this->cacheDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Dump_' .
            TokenGenerator::generateToken('cache');
        $this->cacheDirectories = $cacheDirectories;
    }

    #[\Override]
    public function isApplicable(ContainerInterface $container): bool
    {
        if ($container->hasParameter('kernel.debug') && $container->getParameter('kernel.debug')) {
            $this->cacheDirectories['oro'] = 'oro';
        }
        if (\getenv('CACHE') === 'REDIS' || \getenv('CACHE') === 'DRAGONFLY') {
            return false;
        }

        return $this->isApplicableOS();
    }

    #[\Override]
    public function start(BeforeStartTestsEvent $event): void
    {
        $event->writeln('<info>Creating temp directory</info>', OutputInterface::VERBOSITY_VERBOSE);
        $this->cacheTempDir = $this->createCacheTempDirectory();
        $event->writeln('<info>Dumping cache directories</info>');
        $this->createCacheDumpDirectory();
        $this->dumpCache();
        $event->writeln(
            '<info>Start process for copying Dump to Temp cache directory</info>',
            OutputInterface::VERBOSITY_VERBOSE
        );
        $this->startCopyDumpToTempDir();
    }

    #[\Override]
    public function beforeTest(BeforeIsolatedTestEvent $event): void
    {
    }

    #[\Override]
    public function afterTest(AfterIsolatedTestEvent $event): void
    {
        if (!$this->copyDumpToTempDirProcess) {
            return;
        }

        $this->waitForProcess();
        $event->writeln('<info>Restoring cache directories</info>');
        $this->removeCacheDirs();
        $this->replaceCache();
        $this->startCopyDumpToTempDir();
    }

    #[\Override]
    public function terminate(AfterFinishTestsEvent $event): void
    {
        if (!$this->copyDumpToTempDirProcess) {
            return;
        }

        $event->writeln('<info>Removing Temp and Dump cache directories</info>');
        $this->copyDumpToTempDirProcess->stop();
        $event->writeln('<info>Remove Temp cache dir</info>', OutputInterface::VERBOSITY_VERBOSE);
        $this->removeTempCacheDir();
        $event->writeln('<info>Remove Dump cache dir</info>', OutputInterface::VERBOSITY_VERBOSE);
        $this->removeDumpCacheDir();
    }

    #[\Override]
    public function restoreState(RestoreStateEvent $event): void
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

    #[\Override]
    public function isOutdatedState(): bool
    {
        if (is_dir($this->cacheDumpDir)) {
            return true;
        }

        return false;
    }

    #[\Override]
    public function getTag(): string
    {
        return 'cache';
    }

    /**
     * @param string $commandline The command line to run
     */
    protected function runProcess($commandline): void
    {
        $process = Process::fromShellCommandline($commandline);

        $process
            ->setTimeout(self::TIMEOUT)
            ->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    protected function waitForProcess(): void
    {
        while ($this->copyDumpToTempDirProcess->isRunning()) {
            // waiting for process to finish or fail by timeout
        }

        if (!$this->copyDumpToTempDirProcess->isSuccessful()) {
            throw new ProcessFailedException($this->copyDumpToTempDirProcess);
        }
    }

    protected function createCacheDumpDirectory(): string
    {
        false === is_dir($this->cacheDumpDir) ?: $this->removeDumpCacheDir();
        mkdir($this->cacheDumpDir);

        return realpath($this->cacheDumpDir);
    }

    protected function createCacheTempDirectory(): string
    {
        false === is_dir($this->cacheTempDir) ?: $this->removeTempCacheDir();
        mkdir($this->cacheTempDir);

        return realpath($this->cacheTempDir);
    }

    abstract protected function replaceCache(): void;

    abstract protected function startCopyDumpToTempDir(): void;

    abstract protected function dumpCache(): void;

    abstract protected function removeDumpCacheDir(): void;

    abstract protected function removeTempCacheDir(): void;

    abstract protected function removeCacheDirs(): void;
}
