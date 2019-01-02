<?php

namespace Oro\Bundle\EntityBundle\Event;

use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is fired during building detailed information about entities.
 * @see \Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider
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
