<?php

namespace Oro\Bundle\DigitalAssetBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Tracks DigitalAsset source changes and updates child entity Files as well as triggers images resizing event
 */
class DigitalAssetSourceChangedListener
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param File $entity
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(File $entity, LifecycleEventArgs $args): void
    {
        if ($entity->getParentEntityClass() !== DigitalAsset::class) {
            return;
        }

        $entityManager = $args->getEntityManager();
        $digitalAssetRepository = $entityManager->getRepository(DigitalAsset::class);

        $changeSet = $entityManager->getUnitOfWork()->getEntityChangeSet($entity);
        if (!isset($changeSet['filename'])) {
            return;
        }

        $childFiles = $digitalAssetRepository->findChildFilesByDigitalAssetId($entity->getParentEntityId());
        foreach ($childFiles as $childFile) {
            foreach ($changeSet as $fieldName => $values) {
                $this->propertyAccessor->setValue($childFile, $fieldName, $values[1]);
            }
        }

        $entityManager->flush();
    }
}
