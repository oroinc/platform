<?php

namespace Oro\Bundle\EntityBundle\Event;

use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Symfony\Component\EventDispatcher\Event;

class EntityStructureOptionsEvent extends Event
{
    const EVENT_NAME = 'oro_entity.structure.options';

    /** @var array|EntityStructure[] */
    protected $data = [];

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array|EntityStructure[]
     */
    public function getData()
    {
        return $this->data;
    }
}
