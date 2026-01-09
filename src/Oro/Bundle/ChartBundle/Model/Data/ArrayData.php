<?php

namespace Oro\Bundle\ChartBundle\Model\Data;

/**
 * Provides chart data backed by an in-memory array.
 *
 * This is a simple implementation of {@see DataInterface} that wraps an array of chart data.
 * It is commonly used as the output of data transformers and serves as the final data
 * format passed to chart renderers. This implementation is suitable for small to medium
 * datasets that fit entirely in memory.
 */
class ArrayData implements DataInterface
{
    /**
     * @var array
     */
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    #[\Override]
    public function toArray()
    {
        return $this->data;
    }
}
