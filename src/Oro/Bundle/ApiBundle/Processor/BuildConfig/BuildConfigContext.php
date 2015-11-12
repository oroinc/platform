<?php

namespace Oro\Bundle\ApiBundle\Processor\BuildConfig;

use Oro\Bundle\ApiBundle\Processor\ApiContext;

class BuildConfigContext extends ApiContext
{
    /** FQCN of an entity */
    const CLASS_NAME = 'class';

    /** a definition of filters */
    const FILTERS = 'filters';

    /** a definition of sorters */
    const SORTERS = 'sorters';

    /**
     * Gets FQCN of an entity.
     *
     * @return string|null
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
     * Checks whether a definition of filters exists.
     *
     * @return bool
     */
    public function hasFilters()
    {
        return $this->has(self::FILTERS);
    }

    /**
     * Gets a definition of filters.
     *
     * @return array|null
     */
    public function getFilters()
    {
        return $this->get(self::FILTERS);
    }

    /**
     * Sets a definition of filters.
     *
     * @param array|null $filters
     */
    public function setFilters($filters)
    {
        $this->set(self::FILTERS, $filters);
    }

    /**
     * Checks whether a definition of sorters exists.
     *
     * @return bool
     */
    public function hasSorters()
    {
        return $this->has(self::SORTERS);
    }

    /**
     * Gets a definition of sorters.
     *
     * @return array|null
     */
    public function getSorters()
    {
        return $this->get(self::SORTERS);
    }

    /**
     * Sets a definition of sorters.
     *
     * @param array|null $sorters
     */
    public function setSorters($sorters)
    {
        $this->set(self::SORTERS, $sorters);
    }
}
