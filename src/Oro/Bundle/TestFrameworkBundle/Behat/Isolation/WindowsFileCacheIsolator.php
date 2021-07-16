<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\Process\Process;

/**
 * Manages actualization of cache during tests.
 */
class WindowsFileCacheIsolator extends AbstractFileCacheOsRelatedIsolator
{
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
            AbstractOsRelatedIsolator::WINDOWS_OS,
        ];
    }

    protected function replaceCache()
    {
        $commands = [];
        foreach ($this->cacheDirectories as $directory) {
            $cacheTempDirPath = $this->cacheTempDir.'\\'.$directory;
            if (!is_dir($cacheTempDirPath)) {
                continue;
            }
            $commands[] = sprintf(
                'move %s %s',
                $cacheTempDirPath,
                $this->cacheDir.'\\'.$directory
            );
        }
        foreach ($this->cacheFiles as $file) {
            $cacheTempFilePath = $this->cacheTempDir.'\\'.$file;
            if (!is_file($cacheTempFilePath)) {
                continue;
            }
            $commands[] = sprintf(
                'move %s %s',
                $cacheTempFilePath,
                $this->cacheDir.'\\'.$file
            );
        }

        $this->runProcess(implode(' & ', $commands));
    }

    protected function startCopyDumpToTempDir()
    {
        $commands = [];
        foreach ($this->cacheDirectories as $directory) {
            $commands[] = sprintf(
                'xcopy %s %s /E /R /H /I /K /Y',
                $this->cacheDumpDir.'\\'.$directory,
                $this->cacheTempDir.'\\'.$directory
            );
        }
        foreach ($this->cacheFiles as $file) {
            $commands[] = sprintf(
                'xcopy %s %s /R /H /K /Y',
                $this->cacheDumpDir.'\\'.$file,
                $this->cacheTempDir.'\\'.$file
            );
        }

        $command = implode(' & ', $commands);
        $this->copyDumpToTempDirProcess = Process::fromShellCommandline($command);

        $this->copyDumpToTempDirProcess
            ->setTimeout(self::TIMEOUT)
            ->start();
    }

    protected function dumpCache()
    {
        $commands = [];
        foreach ($this->cacheDirectories as $directory) {
            $cacheDirPath = $this->cacheDir.'\\'.$directory;
            if (!is_dir($cacheDirPath)) {
                continue;
            }
            $commands[] = sprintf(
                'xcopy %s %s /E /R /H /I /K /Y',
                $cacheDirPath,
                $this->cacheDumpDir.'\\'.$directory
            );
        }
        foreach ($this->cacheFiles as $file) {
            $cacheFilePath = $this->cacheDir.'\\'.$file;
            if (!is_dir($cacheFilePath)) {
                continue;
            }
            $commands[] = sprintf(
                'xcopy %s %s /R /H /K /Y',
                $cacheFilePath,
                $this->cacheDumpDir.'\\'.$file
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

    protected function removeCacheDirs()
    {
        $commands = [];
        foreach ($this->cacheDirectories as $directory) {
            $cacheDirPath = $this->cacheDir.'\\'.$directory;
            if (!is_dir($cacheDirPath)) {
                continue;
            }
            $commands[] = sprintf('rd /s /q %s', $cacheDirPath);
        }
        foreach ($this->cacheFiles as $file) {
            $cacheFilePath = $this->cacheDir.'\\'.$file;
            if (!is_file($cacheFilePath)) {
                continue;
            }
            $commands[] = sprintf('del /q %s', $cacheFilePath);
        }

        $this->runProcess(implode(' & ', $commands));
    }
}
