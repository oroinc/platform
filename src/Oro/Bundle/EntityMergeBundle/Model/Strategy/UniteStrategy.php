<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

class UniteStrategy implements StrategyInterface
{
    /**
     * @var AccessorInterface $accessor
     */
    protected $accessor;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param AccessorInterface $accessor
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        AccessorInterface $accessor,
        DoctrineHelper $doctrineHelper
    ) {
        $this->accessor = $accessor;
        $this->doctrineHelper = $doctrineHelper;
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
        foreach ($entities as $entity) {
            $values = $this->accessor->getValue($entity, $fieldMetadata);
            foreach ($values as $value) {
                $key = $this->doctrineHelper->getEntityIdentifierValue($value);
                $relatedEntities[$key] = $value;
            }
        }

        $collection = new ArrayCollection(array_values($relatedEntities));

        if ($fieldMetadata->shouldBeCloned()) {
            $collection = $collection->map(function ($element) {
                return clone $element;
            });
        }

        $this->accessor->setValue($masterEntity, $fieldMetadata, $collection);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(FieldData $fieldData)
    {
        if ($fieldData->getMode() == MergeModes::UNITE) {
            return $fieldData->getMetadata()->isCollection();
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'unite';
    }
}
