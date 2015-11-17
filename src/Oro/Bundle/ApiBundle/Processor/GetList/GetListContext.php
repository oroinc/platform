<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Filter\NullFilterValueAccessor;

class GetListContext extends Context
{
    /** @var FilterValueAccessorInterface */
    private $filterValues;

    /** a configuration of an entity */
    const CONFIG = 'config';

    /** a configuration of an entity filters */
    const CONFIG_FILTERS = 'configFilters';

    /** a configuration of an entity sorters */
    const CONFIG_SORTERS = 'configSorters';

    /** a list of filters is used to add additional restrictions to a query is used to get result data */
    const FILTERS = 'filters';

    /** additional associations required to filter a list of entities */
    const JOINS = 'joins';

    /** a callback that can be used to calculate the total number of records in a list of entities */
    const TOTAL_COUNT_CALLBACK = 'totalCountCallback';

    /**
     * Gets a configuration of an entity
     *
     * @return array|null
     */
    public function getConfig()
    {
        return $this->get(self::CONFIG);
    }

    /**
     * Sets a configuration of an entity
     *
     * @param array|null $config
     */
    public function setConfig($config)
    {
        $this->set(self::CONFIG, $config);
    }

    /**
     * Gets a configuration of an entity filters
     *
     * @return array|null
     */
    public function getConfigOfFilters()
    {
        return $this->get(self::CONFIG_FILTERS);
    }

    /**
     * Sets a configuration of an entity filters
     *
     * @param array|null $config
     */
    public function setConfigOfFilters($config)
    {
        $this->set(self::CONFIG_FILTERS, $config);
    }

    /**
     * Gets a configuration of an entity sorters
     *
     * @return array|null
     */
    public function getConfigOfSorters()
    {
        return $this->get(self::CONFIG_SORTERS);
    }

    /**
     * Sets a configuration of an entity sorters
     *
     * @param array|null $config
     */
    public function setConfigOfSorters($config)
    {
        $this->set(self::CONFIG_SORTERS, $config);
    }

    /**
     * Gets a list of filters is used to add additional restrictions to a query is used to get result data
     *
     * @return FilterCollection
     */
    public function getFilters()
    {
        if (!$this->has(self::FILTERS)) {
            $this->set(self::FILTERS, new FilterCollection());
        }

        return $this->get(self::FILTERS);
    }

    /**
     * Gets a collection of the FilterValue objects that contains all incoming filters
     *
     * @return FilterValueAccessorInterface
     */
    public function getFilterValues()
    {
        if (null === $this->filterValues) {
            $this->filterValues = new NullFilterValueAccessor();
        }

        return $this->filterValues;
    }

    /**
     * Sets an object that will be used to accessing incoming filters
     *
     * @param FilterValueAccessorInterface $accessor
     */
    public function setFilterValues(FilterValueAccessorInterface $accessor)
    {
        $this->filterValues = $accessor;
    }

    /**
     * Gets additional associations required to filter a list of entities
     *
     * @return array|null
     */
    public function getJoins()
    {
        return $this->get(self::JOINS);
    }

    /**
     * Sets additional associations required to filter a list of entities
     *
     * @param array|null $joins
     */
    public function setJoins($joins)
    {
        $this->set(self::JOINS, $joins);
    }

    /**
     * Gets a callback that can be used to calculate the total number of records in a list of entities
     *
     * @return callable|null
     */
    public function getTotalCountCallback()
    {
        return $this->get(self::TOTAL_COUNT_CALLBACK);
    }

    /**
     * Sets a callback that can be used to calculate the total number of records in a list of entities
     *
     * @param callable|null $totalCount
     */
    public function setTotalCountCallback($totalCount)
    {
        $this->set(self::TOTAL_COUNT_CALLBACK, $totalCount);
    }
}
