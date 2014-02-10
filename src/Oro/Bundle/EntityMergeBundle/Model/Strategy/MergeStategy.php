<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Strategy;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Symfony\Component\Security\Core\Util\ClassUtils;

class MergeStategy implements StrategyInterface
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
        $entities      = $fieldData->getEntityData()->getEntities();

        $relatedEntities         = [];
        $doctrineMetadataFactory = $this->entityManager->getMetadataFactory();
        foreach ($entities as $entity) {
            $doctrineMetadata = $doctrineMetadataFactory->getMetadataFor(ClassUtils::getRealClass($entity));
            $ids              = $doctrineMetadata->getIdentifierValues($entity);
            $key              = implode('_', $ids);
            $values           = $this->accessor->getValue($entity, $fieldMetadata);
            foreach ($values as $value) {
                $relatedEntities[$key] = $value;
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
        return ($fieldData->getMode() == MergeModes::MERGE) &&
            ($fieldData->getMetadata()->getDoctrineMetadata()->isCollection() ||
            !$fieldData->getMetadata()->getDoctrineMetadata()->isMappedBySourceEntity());
    }

    /**
     * Get name of field merge strategy
     *
     * @return string
     */
    public function getName()
    {
        // TODO: Implement getName() method.
    }
} 