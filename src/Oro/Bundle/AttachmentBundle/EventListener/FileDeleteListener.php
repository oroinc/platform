<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
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

    public function __construct(FileManager $fileManager, LoggerInterface $logger)
    {
        $this->fileManager = $fileManager;
        $this->logger = $logger;
    }

    public function postRemove(File $file): void
    {
        if ($file->getExternalUrl()) {
            // Externally stored files are not present in filesystem.
            return;
        }

        $this->deleteFromFilesystem((string)$file->getFilename());
    }

    public function postUpdate(File $file, LifecycleEventArgs $args): void
    {
        if ($file->getExternalUrl()) {
            // Externally stored files are not present in filesystem.
            return;
        }

        $changeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($file);
        if (!empty($changeSet['filename'][0])) {
            $this->deleteFromFilesystem($changeSet['filename'][0]);
        }
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
