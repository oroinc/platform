<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class GridToolBarTools extends Element
{
    /**
     * Retrieve current items per page amount from dropdown element
     *
     * @return null|string
     */
    public function getPerPageAmount()
    {
        $perPage = $this->find('css', 'button[data-toggle="dropdown"]');

        return empty($perPage) ? null : $perPage->getText();
    }
}
