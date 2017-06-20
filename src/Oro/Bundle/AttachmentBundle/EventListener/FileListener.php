<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class FileListener
{
    /** @var FileManager */
    protected $fileManager;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param FileManager            $fileManager
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(FileManager $fileManager, TokenAccessorInterface $tokenAccessor)
    {
        $this->fileManager = $fileManager;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        /** @var $entity File */
        $entity = $args->getEntity();
        if ($entity instanceof File) {
            $this->fileManager->preUpload($entity);
            $file = $entity->getFile();
            if (null !== $file && $file->isFile()) {
                $entity->setOwner($this->tokenAccessor->getUser());
            }
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->prePersist($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        /** @var $entity File */
        $entity = $args->getEntity();
        if ($entity instanceof File) {
            $this->fileManager->upload($entity);
            // delete File record from DB if delete button was clicked in UI form and new file was not provided
            if ($entity->isEmptyFile() && null === $entity->getFilename()) {
                $args->getEntityManager()->remove($entity);
            }
            // if needed, delete a previous file from the storage
            $changeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);
            if (isset($changeSet['filename'])) {
                $previousFileName = $changeSet['filename'][0];
                if ($previousFileName) {
                    $this->fileManager->deleteFile($previousFileName);
                }
            }
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->postPersist($args);
    }
}
