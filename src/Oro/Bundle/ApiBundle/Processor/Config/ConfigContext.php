<?php

namespace Oro\Bundle\ApiBundle\Processor\Config;

use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ConfigContext extends ApiContext
{
    /** FQCN of an entity */
    const CLASS_NAME = 'class';

    /** the maximum number of related entities that can be retrieved */
    const MAX_RELATED_RESULTS = 'maxRelatedResults';

    /** a list of additional configuration data that should be returned, for example "filters", "sorters", etc. */
    const EXTRA = 'extra';

    public function __construct()
    {
        $this->set(self::EXTRA, []);
    }

    /**
     * Gets FQCN of an entity.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->get(self::CLASS_NAME);
    }

    /**
     * Sets FQCN of an entity.
     *
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->set(self::CLASS_NAME, $className);
    }

    /**
     * Gets the maximum number of related entities that can be retrieved
     *
     * @return int|null
     */
    public function getMaxRelatedResults()
    {
        return $this->get(self::MAX_RELATED_RESULTS);
    }

    /**
     * Sets the maximum number of related entities that can be retrieved
     *
     * @param int $limit
     */
    public function setMaxRelatedResults($limit)
    {
        $this->set(self::MAX_RELATED_RESULTS, $limit);
    }

    /**
     * Checks if the specified additional configuration data is requested.
     *
     * @param string $extra
     *
     * @return bool
     */
    public function hasExtra($extra)
    {
        return in_array($extra, $this->get(self::EXTRA), true);
    }

    /**
     * Gets a list of requested additional configuration data, for example "filters", "sorters", etc.
     *
     * @return string[]
     */
    public function getExtras()
    {
        return $this->get(self::EXTRA);
    }

    /**
     * Sets additional configuration data that you need to be returned, for example "filters", "sorters", etc.
     *
     * @param string[] $extras
     */
    public function setExtras($extras)
    {
        $this->set(self::EXTRA, $extras);
    }

    /**
     * Checks whether a definition of filters exists.
     *
     * @return bool
     */
    public function hasFilters()
    {
        return $this->has(ConfigUtil::FILTERS);
    }

    /**
     * Gets a definition of filters.
     *
     * @return array|null
     */
    public function getFilters()
    {
        return $this->get(ConfigUtil::FILTERS);
    }

    /**
     * Sets a definition of filters.
     *
     * @param array|null $filters
     */
    public function setFilters($filters)
    {
        $this->set(ConfigUtil::FILTERS, $filters);
    }

    /**
     * Checks whether a definition of sorters exists.
     *
     * @return bool
     */
    public function hasSorters()
    {
        return $this->has(ConfigUtil::SORTERS);
    }

    /**
     * Gets a definition of sorters.
     *
     * @return array|null
     */
    public function getSorters()
    {
        return $this->get(ConfigUtil::SORTERS);
    }

    /**
     * Sets a definition of sorters.
     *
     * @param array|null $sorters
     */
    public function setSorters($sorters)
    {
        $this->set(ConfigUtil::SORTERS, $sorters);
    }
}
