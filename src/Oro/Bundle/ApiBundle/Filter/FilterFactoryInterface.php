<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * Represents a factory to create filters.
 */
interface FilterFactoryInterface
{
    /** this option is used to get the data type if the filter type does not equal to the data type */
    public const DATA_TYPE_OPTION = 'data_type';

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
