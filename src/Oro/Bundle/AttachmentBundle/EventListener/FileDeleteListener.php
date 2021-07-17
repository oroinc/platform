<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
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

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
        $this->logger = new NullLogger();
    }

    public function postRemove(File $file, LifecycleEventArgs $args): void
    {
        $this->deleteFromFilesystem((string)$file->getFilename());
    }

    public function postUpdate(File $file, LifecycleEventArgs $args): void
    {
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
            $this->logger->warning(sprintf('Could not delete file "%s"', $filename), ['e' => $e]);
        }
    }
}
