<?php

namespace Oro\Bundle\SearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class BeforeMapObjectEvent extends Event
{
    const EVENT_NAME = 'oro_search.before_map_object';

    /** @var array */
    protected $mappingConfig;

    /** @var object */
    protected $entity;

    /**
     * @param array  $mappingConfig
     * @param object $entity
     */
    public function __construct(array $mappingConfig, $entity)
    {
        $this->mappingConfig = $mappingConfig;
        $this->entity = $entity;
    }

    /**
     * @return array
     */
    public function getMappingConfig()
    {
        return $this->mappingConfig;
    }

    /**
     * @param array $mappingConfig
     */
    public function setMappingConfig($mappingConfig)
    {
        $this->mappingConfig = $mappingConfig;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
