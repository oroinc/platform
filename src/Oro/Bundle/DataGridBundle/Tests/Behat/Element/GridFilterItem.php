<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

class GridFilterItem extends AbstractGridFilterItem
{
    /**
     * Apply filter to the grid
     */
    #[\Override]
    public function submit()
    {
        $this->find('css', '.filter-update')->click();
    }
}
