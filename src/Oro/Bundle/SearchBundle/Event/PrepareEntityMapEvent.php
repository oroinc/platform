<?php

namespace Oro\Bundle\SearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class PrepareEntityMapEvent extends Event
{
    const EVENT_NAME = 'oro_search.prepare_entity_map';

    /**
     * @var string
     */
    protected $className;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var object
     */
    protected $entity;

    public function __construct($entity, $className, $data)
    {
        $this->className = $className;
        $this->data = $data;
        $this->entity = $entity;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
