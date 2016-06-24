<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class GridPaginator extends Element
{
    /**
     * @return int
     */
    public function getTotalRecordsCount()
    {
        preg_match('/(total of)\s+(?P<count>\d+)/i', $this->getText(), $matches);

        return isset($matches['count']) ? (int) $matches['count'] : 0;
    }

    /**
     * @return int
     */
    public function getTotalPageCount()
    {
        preg_match('/(page:).+(?P<count>\d+).+total/i', $this->getText(), $matches);

        return isset($matches['count']) ? (int) $matches['count'] : 0;
    }
}
