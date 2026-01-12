<?php

namespace Oro\Bundle\EntityMergeBundle\Event;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when field merge data is created during the merge process.
 *
 * This event is triggered after a FieldData object is instantiated for each field
 * of an entity being merged, allowing listeners to inspect or modify field-level
 * merge behavior. Listeners can use this event to customize how specific fields
 * are merged based on their metadata and context.
 */
class FieldDataEvent extends Event
{
    /**
     * @var FieldData
     */
    protected $fieldData;

    public function __construct(FieldData $fieldData)
    {
        $this->fieldData = $fieldData;
    }

    /**
     * Get merge field data
     *
     * @return FieldData
     */
    public function getFieldData()
    {
        return $this->fieldData;
    }
}
