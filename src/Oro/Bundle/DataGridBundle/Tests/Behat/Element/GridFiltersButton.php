<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class GridFiltersButton extends Element
{
    public function open()
    {
        if (!$this->isOpen()) {
            $this->click();
        }
    }

    public function close()
    {
        if ($this->isOpen()) {
            $this->click();
        }
    }

    /**
     * @return bool
     */
    protected function isOpen()
    {
        return $this->hasClass('pressed');
    }
}
