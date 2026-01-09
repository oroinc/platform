<?php

namespace Oro\Bundle\EntityBundle\Event;

use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched during the building of detailed entity structure information.
 *
 * This event allows listeners to modify or extend the entity structure data that describes
 * entities, their fields, and relationships.
 * It is used by {@see \Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider} to collect entity metadata
 * for various purposes such as form building, API documentation, and UI rendering.
 */
class EntityStructureOptionsEvent extends Event
{
    public const EVENT_NAME = 'oro_entity.structure.options';

    /** @var EntityStructure[] */
    protected $data = [];

    /**
     * @param EntityStructure[] $data
     *
     * @return self
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return EntityStructure[]
     */
    public function getData()
    {
        return $this->data;
    }
}
