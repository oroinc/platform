<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Sorter;

/**
 * Provides a way to sort API resource filters by a specific template.
 */
class ByTemplateFiltersSorter implements FiltersSorterInterface
{
    /** @var string[] */
    private array $sortingTemplate;
    private bool $addOtherFiltersToBegin;

    /**
     * @param string[] $sortingTemplate [filter key prefix, ...]
     * @param bool     $addOtherFiltersToBegin
     */
    public function __construct(array $sortingTemplate, bool $addOtherFiltersToBegin = false)
    {
        $this->sortingTemplate = $sortingTemplate;
        $this->addOtherFiltersToBegin = $addOtherFiltersToBegin;
    }

    /**
     * {@inheritDoc}
     */
    public function sortFilters(array $filters): array
    {
        ksort($filters);
        $sortedFilters = [];
        foreach ($this->sortingTemplate as $prefix) {
            $toRemoveKeys = [];
            foreach ($filters as $key => $val) {
                if (str_contains($key, $prefix)) {
                    $sortedFilters[$key] = $val;
                    $toRemoveKeys[] = $key;
                }
            }
            foreach ($toRemoveKeys as $key) {
                unset($filters[$key]);
            }
        }
        if ($filters) {
            if ($this->addOtherFiltersToBegin) {
                $sortedFilters = array_merge($filters, $sortedFilters);
            } else {
                $sortedFilters = array_merge($sortedFilters, $filters);
            }
        }

        return $sortedFilters;
    }
}
