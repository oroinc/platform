<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Import and Export Behat tests isolator.
 */
class ImportExportIsolator implements IsolatorInterface
{
    /** @var KernelInterface */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
        $this->clearStorage();
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        $this->clearStorage();
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return true;
    }

    /** {@inheritdoc} */
    public function restoreState(RestoreStateEvent $event)
    {
        $this->clearStorage();
    }

    /** {@inheritdoc} */
    public function isOutdatedState()
    {
        return count($this->getImportExportFileManager()->getFilesByFilePattern('*')) !== 0;
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return 'Import and Export isolator';
    }

    /** {@inheritdoc} */
    public function getTag()
    {
        return 'import_export';
    }

    private function clearStorage(): void
    {
        $fileManager = $this->getImportExportFileManager();
        $files = $fileManager->getFilesByFilePattern('*');
        foreach ($files as $file) {
            $fileManager->deleteFile($file);
        }
    }

    private function getImportExportFileManager(): FileManager
    {
        return $this->kernel->getContainer()->get('oro_importexport.file.file_manager');
    }
}
