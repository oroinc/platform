<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class WindowsFileCacheIsolator extends OsRelatedIsolator implements IsolatorInterface
{
    const TIMEOUT = 240;

    /** @var array */
    protected $cacheDirectories = [
        'doctrine',
        'oro_data',
        'oro_entities',
        'oro'
    ];

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
        $this->cacheTempDir = $this->cacheDir.'\..\Temp';
        $this->cacheTempDir = $this->createCacheTempDirectory();
        $this->cacheDumpDir = sys_get_temp_dir().'\oro_application_cache_dump';
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
        $event->writeln('<info>Dumping cache directories</info>');
        $this->createCacheDumpDirectory();
        $this->dumpCache();
        $event->writeln('<info>Start process for copying Dump to Temp cache directory</info>');
        $this->startCopyDumpToTempDir();
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {}

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        $this->waitForProcess();
        $this->removeCacheDirs();
        $this->replaceCache();
        $this->startCopyDumpToTempDir();
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
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
        $this->startCopyDumpToTempDir();
        $this->waitForProcess();
        $this->replaceCache();
        $event->writeln('<info>Cache state was restored</info>');
    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return
            $this->isApplicableOS()
            && 'session.handler.native_file' == $container->getParameter('session_handler');
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Cache';
    }

    /** {@inheritdoc} */
    protected function getApplicableOs()
    {
        return [
            OsRelatedIsolator::WINDOWS_OS,
        ];
    }

    /**
     * @param string $dirname
     */
//    protected function rmdir($dirname)
//    {
//        $this->runProcess(sprintf(
//            'rd /s /q %s', $dirname
//        ));
//    }

    protected function replaceCache()
    {
        $commands = [];

        foreach ($this->cacheDirectories as $directory) {
            $commands[] = sprintf(
                "move %s %s",
                $this->cacheTempDir.'\\'.$directory,
                $this->cacheDir.'\\'.$directory
            );
        }

        $this->runProcess(implode(' & ', $commands));
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

    /**
     * @param string $commandline The command line to run
     */
    protected function startProcess($commandline)
    {
        $this->copyDumpToTempDirProcess = new Process($commandline);

        $this->copyDumpToTempDirProcess
            ->setTimeout(self::TIMEOUT)
            ->start();
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

    protected function startCopyDumpToTempDir()
    {
        $commands = [];

        foreach ($this->cacheDirectories as $directory) {
            $commands[] = sprintf(
                "xcopy %s %s /E /R /H /I /K /Y",
                $this->cacheDumpDir.'\\'.$directory,
                $this->cacheTempDir.'\\'.$directory
            );
        }
echo implode(' & ', $commands).PHP_EOL;
        $this->copyDumpToTempDirProcess = new Process(implode(' & ', $commands));

        $this->copyDumpToTempDirProcess
            ->setTimeout(self::TIMEOUT)
            ->start();
    }

    protected function getTempDirName($directory)
    {
        return substr($directory, 0, -1).'_';
    }

    protected function restoreCacheFromDump()
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir);
        }

        $this->runProcess(sprintf(
            'xcopy %s %s /E /R /H /I /K /Y', $this->cacheDumpDir, $this->cacheDir
        ));
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

    protected function dumpCache()
    {
        $commands = [];

        foreach ($this->cacheDirectories as $directory) {
            $commands[] = sprintf(
                'xcopy %s %s /E /R /H /I /K /Y',
                $this->cacheDir.'\\'.$directory,
                $this->cacheDumpDir.'\\'.$directory
            );
        }


        $this->runProcess(implode(' & ', $commands));
    }

    protected function removeDumpCacheDir()
    {
        $this->runProcess(
            sprintf('rd /s /q %s', $this->cacheDumpDir)
        );
    }

    protected function removeTempCacheDir()
    {
        $this->runProcess(
            sprintf('rd /s /q %s', $this->cacheTempDir)
        );
    }

    protected function removeTempCacheDirs()
    {
        $commands = [];

        foreach ($this->cacheDirectories as $directory) {
            $dirPath = $this->cacheTempDir.'\\'.$directory;
            if (is_dir($dirPath)) {
                $commands[] = sprintf('rd /s /q %s', $dirPath);
            }
        }

        if (0 !== count($commands)) {
            $this->runProcess(implode(' & ', $commands));
        }
    }

    protected function removeCacheDirs()
    {
        $commands = [];

        foreach ($this->cacheDirectories as $directory) {
            $commands[] = sprintf('rd /s /q %s', $this->cacheDir.'\\'.$directory);
        }

        $this->runProcess(implode(' & ', $commands));
    }

    protected function renameTempToCacheDir()
    {
//        $filesystem = new Filesystem();
//        $filesystem->rename($this->cacheTempDir, $this->cacheDir);
        var_dump('Now try to deal with this shit');
        sleep(10);
        $this->removeCacheDirs();
        var_dump('Никогда такого небыло и вот опять');
        sleep(1200);
        $this->runProcess(sprintf(
            'cd %s & ren %s %s',
            $this->cacheParentDir,
            basename($this->cacheTempDir),
            basename($this->cacheDir)
        ));
        $this->removeTempCacheDirs();
    }

    protected function moveTempToCache()
    {
        echo sprintf(
            'robocopy %s %s *.* /E /J /MOVE',
            $this->cacheTempDir,
            $this->cacheDir
        ).PHP_EOL;
//        sleep(1200);
        $this->runProcess(sprintf(
            'robocopy %s %s *.* /E /MOVE',
            $this->cacheTempDir,
            $this->cacheDir
        ), self::TIMEOUT);
        $this->removeTempCacheDirs();
    }
}
