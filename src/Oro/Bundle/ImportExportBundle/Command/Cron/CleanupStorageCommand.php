<?php
declare(strict_types=1);

namespace Oro\Bundle\ImportExportBundle\Command\Cron;

use Gaufrette\File;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Manager\ImportExportResultManager;

/**
 * Deletes old temporary import/export files.
 */
class CleanupStorageCommand extends CleanupStorageCommandAbstract
{
    /** @var string */
    protected static $defaultName = 'oro:cron:import-clean-up-storage';

    private FileManager $fileManager;
    private ImportExportResultManager $importExportResultManager;

    public function __construct(FileManager $fileManager, ImportExportResultManager $importExportResultManager)
    {
        $this->fileManager = $fileManager;
        $this->importExportResultManager = $importExportResultManager;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return '0 0 */1 * *';
    }

    /**
     * {@inheritdoc}
     */
    protected function getFilesForDeletion($from, $to): array
    {
        $this->importExportResultManager->markResultsAsExpired($from, $to);
        return $this->fileManager->getFilesByPeriod($from, $to);
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteFile(File $file): void
    {
        $this->fileManager->deleteFile($file);
    }
}
