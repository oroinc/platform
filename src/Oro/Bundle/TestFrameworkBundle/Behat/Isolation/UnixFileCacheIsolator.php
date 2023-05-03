<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\Process\Process;

/**
 * Manages actualization of cache during tests.
 */
class UnixFileCacheIsolator extends AbstractFileCacheOsRelatedIsolator
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
            AbstractOsRelatedIsolator::LINUX_OS,
            AbstractOsRelatedIsolator::MAC_OS,
        ];
    }

    protected function replaceCache()
    {
        $commands = [];
        foreach ($this->cacheDirectories as $directory) {
            $cacheTempDirPath = $this->cacheTempDir.DIRECTORY_SEPARATOR.$directory;
            if (!is_dir($cacheTempDirPath)) {
                continue;
            }
            $commands[] = sprintf(
                'mv %s %s',
                $cacheTempDirPath,
                $this->cacheDir.DIRECTORY_SEPARATOR.$directory
            );
        }
        foreach ($this->cacheFiles as $file) {
            $cacheTempFilePath = $this->cacheTempDir.DIRECTORY_SEPARATOR.$file;
            if (!is_file($cacheTempFilePath)) {
                continue;
            }
            $commands[] = sprintf(
                'mv %s %s',
                $cacheTempFilePath,
                $this->cacheDir.DIRECTORY_SEPARATOR.$file
            );
        }

        $this->runProcess(implode(' && ', $commands));
    }

    protected function startCopyDumpToTempDir()
    {
        // remove old cache dirs
        $this->runProcess(sprintf('rm -rf %s', $this->cacheDir.'_old'));

        $command = sprintf(
            'exec cp -rp %s %s',
            $this->cacheDumpDir.'/*',
            $this->cacheTempDir.DIRECTORY_SEPARATOR
        );
        $this->copyDumpToTempDirProcess = Process::fromShellCommandline($command);

        $this->copyDumpToTempDirProcess
            ->setTimeout(self::TIMEOUT)
            ->start();
    }

    protected function dumpCache()
    {
        $commands = [];
        foreach ($this->cacheDirectories as $directory) {
            $cacheDirPath = $this->cacheDir.DIRECTORY_SEPARATOR.$directory;
            if (!is_dir($cacheDirPath)) {
                continue;
            }
            $commands[] = sprintf(
                'cp -rp %s %s',
                $cacheDirPath,
                $this->cacheDumpDir.DIRECTORY_SEPARATOR.$directory
            );
        }
        foreach ($this->cacheFiles as $file) {
            $cacheFilePath = $this->cacheDir.DIRECTORY_SEPARATOR.$file;
            if (!is_file($cacheFilePath)) {
                continue;
            }
            $commands[] = sprintf(
                'cp -p %s %s',
                $cacheFilePath,
                $this->cacheDumpDir.DIRECTORY_SEPARATOR.$file
            );
        }

        $this->runProcess(implode(' && ', $commands));
    }

    protected function removeDumpCacheDir()
    {
        $this->runProcess(
            sprintf('rm -rf %s', $this->cacheDumpDir)
        );
    }

    protected function removeTempCacheDir()
    {
        $this->runProcess(
            sprintf('rm -rf %s', $this->cacheTempDir)
        );
    }

    protected function removeCacheDirs()
    {
        $commands = [
            sprintf('mkdir %s', $this->cacheDir.'_old')
        ];
        foreach ($this->cacheDirectories as $directory) {
            $cacheDirPath = $this->cacheDir.DIRECTORY_SEPARATOR.$directory;
            $oldCacheDirPath = $this->cacheDir.'_old'.DIRECTORY_SEPARATOR.$directory;
            if (!is_dir($cacheDirPath)) {
                continue;
            }
            $commands[] = sprintf('mv %s %s', $cacheDirPath, $oldCacheDirPath);
        }
        foreach ($this->cacheFiles as $file) {
            $cacheFilePath = $this->cacheDir.DIRECTORY_SEPARATOR.$file;
            if (!is_file($cacheFilePath)) {
                continue;
            }
            $commands[] = sprintf('mv %s %s', $cacheFilePath, $oldCacheDirPath);
        }

        $this->runProcess(implode(' && ', $commands));
    }
}
