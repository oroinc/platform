<?php

declare(strict_types=1);

namespace Oro\Bundle\ImportExportBundle\Command\Cron;

use Gaufrette\File;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Manager\ImportExportResultManager;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Deletes old temporary import/export files.
 */
#[AsCommand(name: 'oro:cron:import-clean-up-storage')]
class CleanupStorageCommand extends CleanupStorageCommandAbstract
{
    private FileManager $fileManager;
    private ImportExportResultManager $importExportResultManager;

    public function __construct(FileManager $fileManager, ImportExportResultManager $importExportResultManager)
    {
        $this->fileManager = $fileManager;
        $this->importExportResultManager = $importExportResultManager;

        parent::__construct();
    }

    #[\Override]
    public function getDefaultDefinition(): string
    {
        return '0 0 */1 * *';
    }

    #[\Override]
    protected function getFilesForDeletion($from, $to): array
    {
        $this->importExportResultManager->markResultsAsExpired($from, $to);
        return $this->fileManager->getFilesByPeriod($from, $to);
    }

    #[\Override]
    protected function deleteFile(File $file): void
    {
        $this->fileManager->deleteFile($file);
    }
}
