<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;

class EntityToIdTransformer extends AbstractEntityAssociationTransformer
{
    /** @var IncludedEntityCollection|null */
    protected $includedEntities;

    /**
     * @param ManagerRegistry               $doctrine
     * @param AssociationMetadata           $metadata
     * @param IncludedEntityCollection|null $includedEntities
     */
    public function __construct(
        ManagerRegistry $doctrine,
        AssociationMetadata $metadata,
        IncludedEntityCollection $includedEntities = null
    ) {
        parent::__construct($doctrine, $metadata);
        $this->includedEntities = $includedEntities;
    }

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return object
     */
    protected function getEntity($entityClass, $entityId)
    {
        $entity = $this->getIncludedEntity($entityClass, $entityId);
        if (null === $entity) {
            $entity = $this->loadEntity($entityClass, $entityId);
        }

        return $entity;
    }

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return object|null
     */
    protected function getIncludedEntity($entityClass, $entityId)
    {
        if (null === $this->includedEntities) {
            return null;
        }

        if ($this->includedEntities->isPrimaryEntity($entityClass, $entityId)) {
            return $this->includedEntities->getPrimaryEntity();
        }

        return $this->includedEntities->get($entityClass, $entityId);
    }
}
