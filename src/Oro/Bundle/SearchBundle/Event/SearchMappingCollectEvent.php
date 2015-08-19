<?php

namespace Oro\Bundle\SearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * This event allow to change search mapping config before the first usage of this mapping config
 */
class SearchMappingCollectEvent extends Event
{
    const EVENT_NAME = 'oro_search.search_mapping_collect';

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
     *
     * @param array $mappingConfig
     */
    public function setMappingConfig($mappingConfig)
    {
        $this->mappingConfig = $mappingConfig;
    }
}
