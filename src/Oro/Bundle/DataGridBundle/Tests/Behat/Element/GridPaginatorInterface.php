<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

interface GridPaginatorInterface
{
    /**
     * @return int
     */
    public function getTotalRecordsCount();

    /**
     * @return int
     */
    public function getTotalPageCount();
}
