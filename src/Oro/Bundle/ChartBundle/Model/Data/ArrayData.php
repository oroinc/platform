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
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->data;
    }
}
