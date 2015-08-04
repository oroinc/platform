<?php

namespace Oro\Bundle\SearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class PrepareEntityMapEvent extends Event
{
    const EVENT_NAME = 'oro_search.prepare_entity_map';

    /** @var string */
    protected $className;

    /** @var array */
    protected $data;

    /** @var object */
    protected $entity;

    /** @var array */
    protected $entityMapping;

    /**
     * @param object $entity
     * @param string $className
     * @param array  $data
     * @param array  $entityMapping
     */
    public function __construct($entity, $className, $data, $entityMapping)
    {
        $this->className     = $className;
        $this->data          = $data;
        $this->entity        = $entity;
        $this->entityMapping = $entityMapping;
    }

    /**
     * @return array
     */
    public function getEntityMapping()
    {
        return $this->entityMapping;
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
