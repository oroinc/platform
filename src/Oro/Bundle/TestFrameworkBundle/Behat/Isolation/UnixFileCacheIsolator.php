<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class UnixFileCacheIsolator extends AbstractFileCacheOsRelatedIsolator implements IsolatorInterface
{
    /** @var array */
    protected $cacheDirectories = [
        'doctrine',
        'oro_data',
        'oro_entities',
        'oro'
    ];

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
            AbstractOsRelatedIsolator::LINUX_OS,
            AbstractOsRelatedIsolator::MAC_OS,
        ];
    }

    protected function replaceCache()
    {
        $commands = [];

        foreach ($this->cacheDirectories as $directory) {
            $commands[] = sprintf(
                "mv %s %s",
                $this->cacheTempDir.'/'.$directory,
                $this->cacheDir.'/'.$directory
            );
        }

        $this->runProcess(implode(' && ', $commands));
    }

    protected function startCopyDumpToTempDir()
    {
        $this->copyDumpToTempDirProcess = new Process(sprintf(
            "exec cp -r %s %s",
            $this->cacheDumpDir.'/*',
            $this->cacheTempDir.'/'
        ));

        $this->copyDumpToTempDirProcess
            ->setTimeout(self::TIMEOUT)
            ->start();
    }

    protected function dumpCache()
    {
        $commands = [];

        foreach ($this->cacheDirectories as $directory) {
            $commands[] = sprintf(
                'cp -r %s %s',
                $this->cacheDir.'/'.$directory,
                $this->cacheDumpDir.'/'.$directory
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
            $commands[] = sprintf('rm -rf %s', $this->cacheDir.'/'.$directory);
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
