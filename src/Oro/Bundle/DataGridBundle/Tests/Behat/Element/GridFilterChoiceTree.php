<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

class GridFilterChoiceTree extends GridFilterStringItem
{
    /**
     * Set value to choice input field
     *
     * @param string|array $value
     */
    public function setFilterValue($value)
    {
        /** @var ChoiceTreeInput $input */
        $input = $this->getElement('GridFilterChoiceTreeValueInput');
        $input->setValue($value);
    }
}
