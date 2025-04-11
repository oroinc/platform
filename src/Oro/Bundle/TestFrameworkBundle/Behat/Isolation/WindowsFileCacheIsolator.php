<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\Process\Process;

/**
 * Manages actualization of cache during tests.
 */
class WindowsFileCacheIsolator extends AbstractFileCacheOsRelatedIsolator
{
    #[\Override]
    public function getName(): string
    {
        return 'Cache';
    }

    #[\Override]
    protected function getApplicableOs(): array
    {
        return [
            AbstractOsRelatedIsolator::WINDOWS_OS,
        ];
    }

    #[\Override]
    protected function replaceCache(): void
    {
        $commands = [];
        foreach ($this->cacheDirectories as $directory) {
            $cacheTempDirPath = $this->cacheTempDir . '\\' . $directory;
            if (!is_dir($cacheTempDirPath)) {
                continue;
            }
            $commands[] = sprintf(
                'move %s %s',
                $cacheTempDirPath,
                $this->cacheDir . '\\' . $directory
            );
        }

        $this->runProcess(implode(' & ', $commands));
    }

    #[\Override]
    protected function startCopyDumpToTempDir(): void
    {
        $commands = [];
        foreach ($this->cacheDirectories as $directory) {
            $commands[] = sprintf(
                'xcopy %s %s /E /R /H /I /K /Y',
                $this->cacheDumpDir . '\\' . $directory,
                $this->cacheTempDir . '\\' . $directory
            );
        }

        $command = implode(' & ', $commands);
        $this->copyDumpToTempDirProcess = Process::fromShellCommandline($command);

        $this->copyDumpToTempDirProcess
            ->setTimeout(self::TIMEOUT)
            ->start();
    }

    #[\Override]
    protected function dumpCache(): void
    {
        $commands = [];
        foreach ($this->cacheDirectories as $directory) {
            $cacheDirPath = $this->cacheDir . '\\' . $directory;
            if (!is_dir($cacheDirPath)) {
                continue;
            }
            $commands[] = sprintf(
                'xcopy %s %s /E /R /H /I /K /Y',
                $cacheDirPath,
                $this->cacheDumpDir . '\\' . $directory
            );
        }

        $this->runProcess(implode(' & ', $commands));
    }

    #[\Override]
    protected function removeDumpCacheDir(): void
    {
        $this->runProcess(
            sprintf('rd /s /q %s', $this->cacheDumpDir)
        );
    }

    #[\Override]
    protected function removeTempCacheDir(): void
    {
        $this->runProcess(
            sprintf('rd /s /q %s', $this->cacheTempDir)
        );
    }

    #[\Override]
    protected function removeCacheDirs(): void
    {
        $commands = [];
        foreach ($this->cacheDirectories as $directory) {
            $cacheDirPath = $this->cacheDir . '\\' . $directory;
            if (!is_dir($cacheDirPath)) {
                continue;
            }
            $commands[] = sprintf('rd /s /q %s', $cacheDirPath);
        }

        $this->runProcess(implode(' & ', $commands));
    }
}
