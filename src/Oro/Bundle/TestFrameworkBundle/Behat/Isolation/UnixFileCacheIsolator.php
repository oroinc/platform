<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Process;

class UnixFileCacheIsolator extends AbstractFileCacheOsRelatedIsolator implements IsolatorInterface
{
    /** @var array */
    protected $cacheDirectories = [
        'doctrine',
        'oro_data',
        'oro_entities',
    ];

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        if ($container->hasParameter('kernel.debug') && $container->getParameter('kernel.debug')) {
            $this->cacheDirectories['oro'] = 'oro';
        }

        return $this->isApplicableOS();
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
                "mv %s %s",
                $cacheTempDirPath,
                $this->cacheDir.DIRECTORY_SEPARATOR.$directory
            );
        }

        $this->runProcess(implode(' && ', $commands));
    }

    protected function startCopyDumpToTempDir()
    {
        $this->copyDumpToTempDirProcess = new Process(sprintf(
            "exec cp -rp %s %s",
            $this->cacheDumpDir.'/*',
            $this->cacheTempDir.DIRECTORY_SEPARATOR
        ));

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
        $commands = [];

        foreach ($this->cacheDirectories as $directory) {
            $cacheDirPath = $this->cacheDir.DIRECTORY_SEPARATOR.$directory;

            if (!is_dir($cacheDirPath)) {
                continue;
            }

            $commands[] = sprintf('rm -rf %s', $cacheDirPath);
        }

        $this->runProcess(implode(' && ', $commands));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Cache';
    }
}
