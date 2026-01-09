<?php

namespace Oro\Bundle\ChartBundle\Model\Data;

/**
 * Defines the contract for chart data objects.
 *
 * Implementations of this interface represent chart data in various forms (arrays, database
 * queries, etc.) and provide a unified way to access that data as an array. This abstraction
 * allows different data sources to be used interchangeably with chart transformers and renderers.
 */
interface DataInterface
{
    /**
     * Converts chart data to array
     *
     * @return array
     */
    public function toArray();
}
