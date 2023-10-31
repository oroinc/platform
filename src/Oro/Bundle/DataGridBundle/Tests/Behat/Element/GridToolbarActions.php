<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class GridToolbarActions extends Element
{
    public function getActionByTitle($title)
    {
        $element = $this->elementFactory->createElement('Grid Toolbar Action ' . $title);
        if ($element) {
            $element = $this->find('xpath', $element->getXpath());
        }

        return $element;
    }
}
