<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;

class FileListener
{
    /** @var FileManager */
    protected $fileManager;

    /** @var ServiceLink */
    protected $securityFacadeLink;

    /**
     * @param FileManager $fileManager
     * @param ServiceLink $securityFacadeLink
     */
    public function __construct(FileManager $fileManager, ServiceLink $securityFacadeLink)
    {
        $this->fileManager = $fileManager;
        $this->securityFacadeLink = $securityFacadeLink;
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
                $entity->setOwner($this->securityFacadeLink->getService()->getLoggedUser());
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
