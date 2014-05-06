<?php

namespace Oro\Bundle\ChartBundle\Model\Data;

interface DataInterface
{
    /**
     * Converts chart data to array
     *
     * @return array
     */
    public function toArray();
}
