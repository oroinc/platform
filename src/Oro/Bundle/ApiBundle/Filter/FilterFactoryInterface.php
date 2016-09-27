<?php

namespace Oro\Bundle\ApiBundle\Filter;

interface FilterFactoryInterface
{
    /** use this option in case if the filter type does not equal to the data type */
    const DATA_TYPE_OPTION = 'data_type';

    /**
     * Creates a new instance of filter.
     *
     * @param string $filterType The type of a filter.
     * @param array  $options    The filter options.
     *
     * @return StandaloneFilter|null
     */
    public function createFilter($filterType, array $options = []);
}
