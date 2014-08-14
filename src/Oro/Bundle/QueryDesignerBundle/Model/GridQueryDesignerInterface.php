<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

interface GridQueryDesignerInterface
{
    /**
     * Get the prefix for grid name
     *
     * @return string
     */
    public function getGridPrefix();
}
