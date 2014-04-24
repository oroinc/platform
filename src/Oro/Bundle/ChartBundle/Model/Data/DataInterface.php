<?php

namespace Oro\Bundle\ChartBundle\Model\Data;

interface DataInterface
{
    /**
     * Converts chart data to array
     *
     * @return mixed
     */
    public function toArray();
}
