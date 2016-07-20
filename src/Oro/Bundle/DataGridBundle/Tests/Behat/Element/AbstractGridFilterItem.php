<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

abstract class AbstractGridFilterItem extends Element
{
    /**
     * Make filter active. Only one filter can be active at one time
     */
    public function open()
    {
        if (!$this->isOpen()) {
            $this->find('css', '.filter-criteria-selector span.caret')->click();
        }
    }

    public function isOpen()
    {
        $this->hasClass('open-filter');
    }
}
