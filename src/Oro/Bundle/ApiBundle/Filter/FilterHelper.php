<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * A set of reusable utility methods that can be used to get filter values.
 */
class FilterHelper
{
    private const PAGE_NUMBER_FIELD_NAME = '__page_number__';
    private const PAGE_SIZE_FIELD_NAME   = '__page_size__';
    private const SORT_FIELD_NAME        = '__sort__';

    /** @var FilterCollection */
    private $filters;

    /** @var FilterValueAccessorInterface */
    private $filterValues;

    /** @var array [field name => [FilterValue|null, filterKey, filter], ...] */
    private $filterMap;

    /**
     * @param FilterCollection             $filters
     * @param FilterValueAccessorInterface $filterValues
     */
    public function __construct(FilterCollection $filters, FilterValueAccessorInterface $filterValues)
    {
        $this->filters = $filters;
        $this->filterValues = $filterValues;
    }

    /**
     * Return a value of "page number" filter.
     *
     * @return int|null
     */
    public function getPageNumber(): ?int
    {
        $result = null;
        $filterValue = $this->getFilterValue(self::PAGE_NUMBER_FIELD_NAME);
        if ($filterValue) {
            $result = $filterValue->getValue();
        }

        return $result;
    }

    /**
     * Return a value of "page size" filter.
     *
     * @return int|null
     */
    public function getPageSize(): ?int
    {
        $result = null;
        $filterValue = $this->getFilterValue(self::PAGE_SIZE_FIELD_NAME);
        if ($filterValue) {
            $result = $filterValue->getValue();
        }

        return $result;
    }

    /**
     * Return a value of "sort" filter.
     *
     * @return array|null [field name => direction, ...] or NULL
     */
    public function getOrderBy(): ?array
    {
        $result = null;
        $filterValue = $this->getFilterValue(self::SORT_FIELD_NAME);
        if ($filterValue) {
            $result = $filterValue->getValue();
        }

        return $result;
    }

    /**
     * Returns a value of a given boolean filter.
     *
     * @param string $fieldName
     *
     * @return bool|null
     */
    public function getBooleanFilterValue(string $fieldName): ?bool
    {
        $result = null;
        $filterValue = $this->getFilterValue($fieldName);
        if ($filterValue) {
            $result = $filterValue->getValue();
            if (ComparisonFilter::NEQ === $filterValue->getOperator()) {
                $result = !$result;
            }
        }

        return $result;
    }

    /**
     * Returns a value of a given filter.
     *
     * @param string $fieldName
     *
     * @return FilterValue|null
     */
    public function getFilterValue(string $fieldName): ?FilterValue
    {
        $this->ensureInitialized();

        if (!isset($this->filterMap[$fieldName])) {
            return null;
        }

        $item = $this->filterMap[$fieldName];
        $filterValue = $item[0];
        if (null === $filterValue && $item[2] instanceof StandaloneFilterWithDefaultValue) {
            $filterValue = new FilterValue($item[1], $item[2]->getDefaultValue(), StandaloneFilter::EQ);
            $this->filterMap[$fieldName][0] = $filterValue;
        }

        return $filterValue;
    }

    /**
     * Makes sure that $this->filterMap is initialized.
     */
    private function ensureInitialized(): void
    {
        if (null !== $this->filterMap) {
            return;
        }

        $this->filterMap = [];
        foreach ($this->filters as $filterKey => $filter) {
            if ($filter instanceof FieldAwareFilterInterface) {
                $this->filterMap[$filter->getField()] = [
                    $this->filterValues->get($filterKey),
                    $filterKey,
                    $filter
                ];
            } elseif ($filter instanceof PageNumberFilter) {
                $this->filterMap[self::PAGE_NUMBER_FIELD_NAME] = [
                    $this->filterValues->get($filterKey),
                    $filterKey,
                    $filter
                ];
            } elseif ($filter instanceof PageSizeFilter) {
                $this->filterMap[self::PAGE_SIZE_FIELD_NAME] = [
                    $this->filterValues->get($filterKey),
                    $filterKey,
                    $filter
                ];
            } elseif ($filter instanceof SortFilter) {
                $this->filterMap[self::SORT_FIELD_NAME] = [
                    $this->filterValues->get($filterKey),
                    $filterKey,
                    $filter
                ];
            }
        }
    }
}
