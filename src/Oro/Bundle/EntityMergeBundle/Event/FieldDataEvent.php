<?php

namespace Oro\Bundle\EntityMergeBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;

class FieldDataEvent extends Event
{
    /**
     * @var FieldData
     */
    protected $fieldData;

    /**
     * @param FieldData $fieldData
     */
    public function __construct(FieldData $fieldData)
    {
        $this->fieldData = $fieldData;
    }

    /**
     * Get merge field data
     *
     * @return FieldData
     */
    public function getEntityData()
    {
        return $this->fieldData;
    }
}
