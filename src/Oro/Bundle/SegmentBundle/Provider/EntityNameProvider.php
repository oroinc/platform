<?php

namespace Oro\Bundle\SegmentBundle\Provider;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;

class EntityNameProvider
{
    /** @var string */
    protected $entityName = false;

    /**
     * @param AbstractQueryDesigner $entity
     */
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
