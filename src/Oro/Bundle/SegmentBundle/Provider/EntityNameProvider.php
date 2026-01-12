<?php

namespace Oro\Bundle\SegmentBundle\Provider;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;

/**
 * Provider that tracks the current entity being designed in segment query builder.
 *
 * This provider maintains the context of which entity is currently being worked on
 * during segment creation or editing. It stores the entity name from the query designer
 * and makes it available to other components that need to know the current entity context.
 * This is essential for building segment conditions and validating segment criteria
 * against the correct entity type.
 */
class EntityNameProvider
{
    /** @var string */
    protected $entityName = false;

    public function setCurrentItem(AbstractQueryDesigner $entity)
    {
        $this->entityName = $entity->getEntity();
    }

    /**
     * @return string|boolean
     */
    public function getEntityName()
    {
        return $this->entityName;
    }
}
