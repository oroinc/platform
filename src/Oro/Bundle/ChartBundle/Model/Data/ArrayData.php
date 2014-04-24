<?php

namespace Oro\Bundle\ChartBundle\Model\Data;

class ArrayData implements DataInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Converts chart data to array
     *
     * @return mixed
     */
    public function toArray()
    {
        return $this->data;
    }
}
