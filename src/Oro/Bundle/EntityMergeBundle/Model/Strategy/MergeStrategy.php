<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Strategy;

use Symfony\Component\Security\Core\Util\ClassUtils;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

class MergeStrategy implements StrategyInterface
{
    /**
     * @var AccessorInterface $accessor
     */
    protected $accessor;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param AccessorInterface $accessor
     * @param EntityManager     $entityManager
     */
    public function __construct(AccessorInterface $accessor, EntityManager $entityManager)
    {
        $this->accessor      = $accessor;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(FieldData $fieldData)
    {
        $entityData    = $fieldData->getEntityData();
        $masterEntity  = $entityData->getMasterEntity();
        $fieldMetadata = $fieldData->getMetadata();
        $entities      = $entityData->getEntities();

        $relatedEntities         = [];
        $doctrineMetadataFactory = $this->entityManager->getMetadataFactory();
        foreach ($entities as $entity) {
            $doctrineMetadata = $doctrineMetadataFactory->getMetadataFor(ClassUtils::getRealClass($entity));
            $ids              = $doctrineMetadata->getIdentifierValues($entity);
            $doctrineKey      = implode('_', $ids);
            $values           = $this->accessor->getValue($entity, $fieldMetadata);
            foreach ($values as $key => $value) {
                $relatedEntities[$doctrineKey . '_' . $key] = $value;
            }
        }

        $collection = new ArrayCollection(array_values($relatedEntities));
        $this->accessor->setValue($masterEntity, $fieldMetadata, $collection);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(FieldData $fieldData)
    {
        if ($fieldData->getMode() == MergeModes::MERGE) {
            return $fieldData->getMetadata()->isCollection();
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'merge';
    }
}
