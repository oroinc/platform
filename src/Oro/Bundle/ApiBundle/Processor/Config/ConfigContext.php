<?php

namespace Oro\Bundle\ApiBundle\Processor\Config;

use Oro\Bundle\ApiBundle\Processor\ApiContext;

class ConfigContext extends ApiContext
{
    /** FQCN of an entity */
    const CLASS_NAME = 'class';

    /** the request action, for example "get", "get_list", etc. */
    const REQUEST_ACTION = 'requestAction';

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
     * Gets the request action, for example "get", "get_list", etc.
     *
     * @return string
     */
    public function getRequestAction()
    {
        return $this->get(self::REQUEST_ACTION);
    }

    /**
     * Sets the request action, for example "get", "get_list", etc.
     *
     * @param string $requestAction
     */
    public function setRequestAction($requestAction)
    {
        $this->set(self::REQUEST_ACTION, $requestAction);
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
