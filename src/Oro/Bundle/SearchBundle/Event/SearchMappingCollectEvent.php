<?php

namespace Oro\Bundle\SearchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched to allow modification of search mapping configuration before it is used.
 *
 * This event is fired when the search mapping configuration is being collected and before it is cached or
 * used for the first time. Event listeners can modify the mapping configuration to add, remove, or alter
 * entity search mappings, fields, and aliases dynamically.
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
