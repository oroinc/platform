<?php

namespace Oro\Bundle\ChartBundle\Model\Data;

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
