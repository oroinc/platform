<?php

namespace Oro\Bundle\DigitalAssetBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Reflector\FileReflector;

/**
 * Listens to changes of File::$digitalAsset relation to update File according to DigitalAsset::$sourceFile.
 */
class FileDigitalAssetChangedListener
{
    /** @var FileReflector */
    private $fileReflector;

    /**
     * @param FileReflector $fileReflector
     */
    public function __construct(FileReflector $fileReflector)
    {
        $this->fileReflector = $fileReflector;
    }

    /**
     * @param File $file
     * @param LifecycleEventArgs $args
     */
    public function prePersist(File $file, LifecycleEventArgs $args): void
    {
        /** @var DigitalAsset|null $digitalAsset */
        $digitalAsset = $file->getDigitalAsset();
        if (!$digitalAsset) {
            return;
        }

        $this->fileReflector->reflectFromDigitalAsset($file, $digitalAsset);
    }

    /**
     * @param File $entity
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(File $entity, LifecycleEventArgs $args): void
    {
        $changeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);
        if (!empty($changeSet['digitalAsset'][1])) {
            $this->prePersist($entity, $args);
        }
    }
}
