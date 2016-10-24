<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class GridToolbarActions extends Element
{
    public function getActionByTitle($title)
    {
        return $this->find('css', '[title="'.$title.'"]');
    }
}
