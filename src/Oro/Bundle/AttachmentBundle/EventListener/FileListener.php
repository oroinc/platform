<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Listens on File lifecycle events to handle its upload.
 */
class FileListener
{
    /** @var FileManager */
    protected $fileManager;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    public function __construct(FileManager $fileManager, TokenAccessorInterface $tokenAccessor)
    {
        $this->fileManager = $fileManager;
        $this->tokenAccessor = $tokenAccessor;
    }

    public function prePersist(File $entity, LifecycleEventArgs $args)
    {
        $entityManager = $args->getObjectManager();

        if ($entity->isEmptyFile() && $entityManager->contains($entity)) {
            // Skips updates if file is going to be deleted.
            $entityManager->getUnitOfWork()->clearEntityChangeSet(spl_object_hash($entity));

            $entityManager->refresh($entity);
            $entity->setEmptyFile(true);

            return;
        }

        $this->fileManager->preUpload($entity);

        $file = $entity->getFile();
        if (null !== $file && !$entity->getOwner()) {
            $owner = $this->tokenAccessor->getUser();
            if ($owner instanceof User) {
                $entity->setOwner($owner);
            }
        }
    }

    public function preUpdate(File $entity, LifecycleEventArgs $args)
    {
        $this->prePersist($entity, $args);
    }

    public function postPersist(File $entity, LifecycleEventArgs $args)
    {
        $entityManager = $args->getObjectManager();

        // Delete File if it is marked for deletion and new file is not provided.
        if ($entity->isEmptyFile() && !$entity->getFile()) {
            $entityManager->remove($entity);
        } else {
            $this->fileManager->upload($entity);
        }
    }

    public function postUpdate(File $entity, LifecycleEventArgs $args)
    {
        $this->postPersist($entity, $args);
    }
}
