<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * Provides names of predefined filters.
 */
class FilterNames
{
    /** @var string */
    private $sortFilterName;

    /** @var string */
    private $pageNumberFilterName;

    /** @var string */
    private $pageSizeFilterName;

    /** @var string */
    private $metaPropertyFilterName;

    /** @var string|null */
    private $dataFilterGroupName;

    /** @var string|null */
    private $fieldsFilterGroupName;

    /** @var string|null */
    private $includeFilterName;

    /**
     * @param string      $sortFilterName
     * @param string      $pageNumberFilterName
     * @param string      $pageSizeFilterName
     * @param string      $metaPropertyFilterName
     * @param string|null $dataFilterGroupName
     * @param string|null $fieldsFilterGroupName
     * @param string|null $includeFilterName
     */
    public function __construct(
        string $sortFilterName,
        string $pageNumberFilterName,
        string $pageSizeFilterName,
        string $metaPropertyFilterName,
        string $dataFilterGroupName = null,
        string $fieldsFilterGroupName = null,
        string $includeFilterName = null
    ) {
        $this->sortFilterName = $sortFilterName;
        $this->pageNumberFilterName = $pageNumberFilterName;
        $this->pageSizeFilterName = $pageSizeFilterName;
        $this->metaPropertyFilterName = $metaPropertyFilterName;
        $this->dataFilterGroupName = $dataFilterGroupName;
        $this->fieldsFilterGroupName = $fieldsFilterGroupName;
        $this->includeFilterName = $includeFilterName;
    }

    /**
     * Gets the name of a filter that can be used to specify how a result collection should be sorted.
     * @see \Oro\Bundle\ApiBundle\Filter\SortFilter
     *
     * @return string
     */
    public function getSortFilterName(): string
    {
        return $this->sortFilterName;
    }

    /**
     * Gets the name of a filter that can be used to specify the page number.
     * @see \Oro\Bundle\ApiBundle\Filter\PageNumberFilter
     *
     * @return string
     */
    public function getPageNumberFilterName(): string
    {
        return $this->pageNumberFilterName;
    }

    /**
     * Gets the name of a filter that can be used to specify the maximum number of records on one page.
     * @see \Oro\Bundle\ApiBundle\Filter\PageSizeFilter
     *
     * @return string
     */
    public function getPageSizeFilterName(): string
    {
        return $this->pageSizeFilterName;
    }

    /**
     * Gets the name of a filter that can be used to add entity meta properties to the result.
     * @see \Oro\Bundle\ApiBundle\Filter\MetaPropertyFilter
     * @see \Oro\Bundle\ApiBundle\Processor\Shared\AddMetaPropertyFilter
     * @see \Oro\Bundle\ApiBundle\Processor\Shared\HandleMetaPropertyFilter
     *
     * @return string
     */
    public function getMetaPropertyFilterName(): string
    {
        return $this->metaPropertyFilterName;
    }

    /**
     * Gets the name of a group for all data filters.
     * E.g. if the group is "filter" then the full name of data filters will be "filter[fieldName]".
     * @see \Oro\Bundle\ApiBundle\Filter\ComparisonFilter
     * @see \Oro\Bundle\ApiBundle\Processor\Shared\RegisterDynamicFilters
     * @see \Oro\Bundle\ApiBundle\Processor\Shared\RegisterConfiguredFilters
     *
     * @return string|null
     */
    public function getDataFilterGroupName(): ?string
    {
        return $this->dataFilterGroupName;
    }

    /**
     * Gets the name of a group for all filters that can be used to filter entity fields that should be returned.
     * E.g. if the group is "fields" then the full name of such filters will be "fields[entityType]".
     * @see \Oro\Bundle\ApiBundle\Filter\FieldsFilter
     * @see \Oro\Bundle\ApiBundle\Processor\Shared\AddFieldsFilter
     * @see \Oro\Bundle\ApiBundle\Processor\Shared\HandleFieldsFilter
     *
     * @return string|null
     */
    public function getFieldsFilterGroupName(): ?string
    {
        return $this->fieldsFilterGroupName;
    }

    /**
     * Gets the template to build filters that can be used to filter entity fields that should be returned.
     * E.g. if the group of the fields filters is "fields" then the template will be "fields[%s]".
     *
     * @return string|null
     */
    public function getFieldsFilterTemplate(): ?string
    {
        if (!$this->fieldsFilterGroupName) {
            return null;
        }

        return $this->fieldsFilterGroupName . '[%s]';
    }

    /**
     * Gets the name of a filter that can be used to add a list of related entities to the result.
     * @see \Oro\Bundle\ApiBundle\Filter\IncludeFilter
     * @see \Oro\Bundle\ApiBundle\Processor\Shared\AddIncludeFilter
     * @see \Oro\Bundle\ApiBundle\Processor\Shared\HandleIncludeFilter
     *
     * @return string|null
     */
    public function getIncludeFilterName(): ?string
    {
        return $this->includeFilterName;
    }
}
