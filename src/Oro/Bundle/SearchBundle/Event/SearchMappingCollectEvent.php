<?php

namespace Oro\Bundle\SearchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event allow to change search mapping config before the first usage of this mapping config
 */
class SearchMappingCollectEvent extends Event
{
    /** @var array */
    protected $mappingConfig;

    /**
     * @param array $mappingConfig
     */
    public function __construct($mappingConfig)
    {
        $this->mappingConfig = $mappingConfig;
    }

    /**
     * Return mapping config array
     *
     * @return array
     */
    public function getMappingConfig()
    {
        return $this->mappingConfig;
    }

    /**
     * Set the mapping config array
     */
    public function setMappingConfig(array $mappingConfig)
    {
        $this->mappingConfig = $mappingConfig;
    }
}
