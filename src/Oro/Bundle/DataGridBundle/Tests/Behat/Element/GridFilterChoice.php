<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Oro\Bundle\FormBundle\Tests\Behat\Element\Select2Entities;

class GridFilterChoice extends GridFilterStringItem
{
    /**
     * Set value to choice input field
     *
     * @param string|array $value
     */
    public function setFilterValue($value)
    {
        /** @var Select2Entities $select2Element */
        $select2Element = $this->getElement('Select2Entities');
        $select2Element->setValue($value);
    }
}
