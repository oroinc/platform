<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\Process\Process;

/**
 * Manages actualization of cache during tests.
 */
class UnixFileCacheIsolator extends AbstractFileCacheOsRelatedIsolator
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
            AbstractOsRelatedIsolator::LINUX_OS,
            AbstractOsRelatedIsolator::MAC_OS,
        ];
    }

    #[\Override]
    protected function replaceCache(): void
    {
        $commands = [];
        foreach ($this->cacheDirectories as $directory) {
            $cacheTempDirPath = $this->cacheTempDir . DIRECTORY_SEPARATOR . $directory;
            if (!is_dir($cacheTempDirPath)) {
                continue;
            }
            $commands[] = sprintf(
                'mv %s %s',
                $cacheTempDirPath,
                $this->cacheDir . DIRECTORY_SEPARATOR . $directory
            );
        }

        $this->runProcess(implode(' && ', $commands));
    }

    #[\Override]
    protected function startCopyDumpToTempDir(): void
    {
        // remove old cache dirs
        $this->runProcess(sprintf('rm -rf %s', $this->cacheDir . '_old'));

        $command = sprintf(
            'exec cp -rp %s %s',
            $this->cacheDumpDir . '/*',
            $this->cacheTempDir . DIRECTORY_SEPARATOR
        );
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
            $cacheDirPath = $this->cacheDir . DIRECTORY_SEPARATOR . $directory;
            if (!is_dir($cacheDirPath)) {
                continue;
            }
            $commands[] = sprintf(
                'cp -rp %s %s',
                $cacheDirPath,
                $this->cacheDumpDir . DIRECTORY_SEPARATOR . $directory
            );
        }

        $this->runProcess(implode(' && ', $commands));
    }

    #[\Override]
    protected function removeDumpCacheDir(): void
    {
        $this->runProcess(
            sprintf('rm -rf %s', $this->cacheDumpDir)
        );
    }

    #[\Override]
    protected function removeTempCacheDir(): void
    {
        $this->runProcess(
            sprintf('rm -rf %s', $this->cacheTempDir)
        );
    }

    #[\Override]
    protected function removeCacheDirs(): void
    {
        $commands = [
            sprintf('mkdir %s', $this->cacheDir . '_old')
        ];
        foreach ($this->cacheDirectories as $directory) {
            $cacheDirPath = $this->cacheDir . DIRECTORY_SEPARATOR . $directory;
            $oldCacheDirPath = $this->cacheDir . '_old' . DIRECTORY_SEPARATOR . $directory;
            if (!is_dir($cacheDirPath)) {
                continue;
            }
            $commands[] = sprintf('mv %s %s', $cacheDirPath, $oldCacheDirPath);
        }

        $this->runProcess(implode(' && ', $commands));
    }
}
