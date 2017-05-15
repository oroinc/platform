<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Process;

final class WindowsFileCacheIsolator extends AbstractFileCacheOsRelatedIsolator implements IsolatorInterface
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
            $commands[] = sprintf(
                "move %s %s",
                $this->cacheTempDir.'\\'.$directory,
                $this->cacheDir.'\\'.$directory
            );
        }

        $this->runProcess(implode(' & ', $commands));
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

        $this->copyDumpToTempDirProcess = new Process(implode(' & ', $commands));

        $this->copyDumpToTempDirProcess
            ->setTimeout(self::TIMEOUT)
            ->start();
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

    protected function removeCacheDirs()
    {
        $commands = [];

        foreach ($this->cacheDirectories as $directory) {
            $commands[] = sprintf('rd /s /q %s', $this->cacheDir.'\\'.$directory);
        }

        $this->runProcess(implode(' & ', $commands));
    }
}
