<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Psr\Log\LoggerInterface;

/**
 * Listens on File lifecycle events to perform its deletion from filesystem.
 */
class FileDeleteListener
{
    private FileManager $fileManager;
    private LoggerInterface $logger;

    /** The list of the files should be physically removed. */
    private array $filesShouldBeDeleted = [];

    public function __construct(FileManager $fileManager, LoggerInterface $logger)
    {
        $this->fileManager = $fileManager;
        $this->logger = $logger;
    }

    /**
     * Clear collected file names that could stay after previous failed flush.
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $this->filesShouldBeDeleted = [];
    }

    /**
     * Collects file names should be removed because the file deletes at all.
     * The files cannot be removed at this method to be able to cancel deleting if the remove of the
     * File entity transaction was failed.
     */
    public function postRemove(File $file): void
    {
        if ($file->getExternalUrl()) {
            // Externally stored files are not present in filesystem.
            return;
        }

        $this->filesShouldBeDeleted[] = $file->getFilename();
    }

    /**
     * Collects file names should be removed because the file entity will have another file.
     * The files cannot be removed at this method to be able to cancel deleting if the remove of the
     * File entity transaction was failed.
     */
    public function postUpdate(File $file, LifecycleEventArgs $args): void
    {
        if ($file->getExternalUrl()) {
            // Externally stored files are not present in filesystem.
            return;
        }

        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($file);
        if (!empty($changeSet['filename'][0])) {
            $this->filesShouldBeDeleted[] = $changeSet['filename'][0];
        }
    }

    /**
     * Physically removes the files by the list of collected file names.
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        foreach ($this->filesShouldBeDeleted as $item) {
            $this->deleteFromFilesystem($item);
        }

        $this->filesShouldBeDeleted = [];
    }

    private function deleteFromFilesystem(string $filename): void
    {
        try {
            $this->fileManager->deleteFile($filename);
        } catch (\Exception $e) {
            $this->logger->warning(sprintf('Could not delete file "%s"', $filename), ['exception' => $e]);
        }
    }
}
