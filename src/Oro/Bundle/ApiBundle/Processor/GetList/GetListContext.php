<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Filter\NullFilterValueAccessor;
use Oro\Bundle\ApiBundle\Provider\ConfigExtra;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class GetListContext extends Context
{
    /** @var FilterValueAccessorInterface */
    private $filterValues;

    /** a list of filters is used to add additional restrictions to a query is used to get result data */
    const FILTERS = 'filters';

    /** a callback that can be used to calculate the total number of records in a list of entities */
    const TOTAL_COUNT_CALLBACK = 'totalCountCallback';

    /**
     * @param ConfigProvider   $configProvider
     * @param MetadataProvider $metadataProvider
     */
    public function __construct(ConfigProvider $configProvider, MetadataProvider $metadataProvider)
    {
        parent::__construct($configProvider, $metadataProvider);
        $this->setConfigSections([ConfigExtra::FILTERS, ConfigExtra::SORTERS]);
    }

    /**
     * Checks whether a configuration of filters for an entity exists
     *
     * @return bool
     */
    public function hasConfigOfFilters()
    {
        return $this->hasConfigOf(ConfigUtil::FILTERS);
    }

    /**
     * Gets a configuration of filters for an entity
     *
     * @return array|null
     */
    public function getConfigOfFilters()
    {
        return $this->getConfigOf(ConfigUtil::FILTERS);
    }

    /**
     * Sets a configuration of filters for an entity
     *
     * @param array|null $config
     */
    public function setConfigOfFilters($config)
    {
        $this->setConfigOf(ConfigUtil::FILTERS, $config);
    }

    /**
     * Checks whether a configuration of sorters for an entity exists
     *
     * @return bool
     */
    public function hasConfigOfSorters()
    {
        return $this->hasConfigOf(ConfigUtil::SORTERS);
    }

    /**
     * Gets a configuration of sorters for an entity
     *
     * @return array|null
     */
    public function getConfigOfSorters()
    {
        return $this->getConfigOf(ConfigUtil::SORTERS);
    }

    /**
     * Sets a configuration of sorters for an entity
     *
     * @param array|null $config
     */
    public function setConfigOfSorters($config)
    {
        $this->setConfigOf(ConfigUtil::SORTERS, $config);
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
