<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Listens on File lifecycle events to perform its deletion from filesystem.
 */
class FileDeleteListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var FileManager */
    private $fileManager;

    private array $filesShouldBeDeleted = [];

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
        $this->logger = new NullLogger();
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
    public function postRemove(File $file, LifecycleEventArgs $args): void
    {
        $this->filesShouldBeDeleted[] = $file->getFilename();
    }

    /**
     * Collects file names should be removed because the file entity will have another file.
     * The files cannot be removed at this method to be able to cancel deleting if the remove of the
     * File entity transaction was failed.
     */
    public function postUpdate(File $file, LifecycleEventArgs $args): void
    {
        $changeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($file);
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
            $this->logger->warning(sprintf('Could not delete file "%s"', $filename), ['e' => $e]);
        }
    }
}
