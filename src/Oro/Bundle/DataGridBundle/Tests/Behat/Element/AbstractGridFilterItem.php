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
            $this->toggleFilter();
        }
    }

    public function close()
    {
        if ($this->isOpen()) {
            $this->toggleFilter();
        }
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return $this->hasClass('open-filter');
    }

    public function reset()
    {
        $this->find('css', 'span.reset-filter')->click();
        $this->getDriver()->waitForAjax();
    }

    private function toggleFilter()
    {
        $this->find('css', '.filter-criteria-selector')->click();
    }
}
