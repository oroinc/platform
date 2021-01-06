<?php

namespace Oro\Bundle\FilterBundle\Filter;

/**
 * This interface is added to avoid BC breaks and it will be removed in v4.2.
 * The prepareData() will be added to {@see \Oro\Bundle\FilterBundle\Filter\FilterInterface}.
 */
interface FilterPrepareDataInterface
{
    /**
     * Prepares data to be ready to pass to {@see apply()} method.
     * This method does a filter value normalization the similar as an appropriate filter form,
     * but without data validation.
     * This method is used instead of the form when the data are already valid,
     * e.g. when loading a segment or a report data.
     *
     * @param array $data
     *
     * @return array
     *
     * @throw \Throwable if a filter value normalization failed
     */
    public function prepareData(array $data): array;
}
