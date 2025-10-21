<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\VariableStorage;

class GridFilterChoiceTree extends GridFilterStringItem
{
    /**
     * Set value to choice input field
     *
     * @param string|array $value
     */
    public function setFilterValue($value)
    {
        $value = VariableStorage::normalizeValue($value);
        /** @var ChoiceTreeInput $input */
        $input = $this->getElement('GridFilterChoiceTreeValueInput');
        $input->setValue($value);
    }

    /**
     * Check that the item exists/not exists in grid filter options
     */
    public function checkValue(string $value, bool $isShouldSee): void
    {
        $value = VariableStorage::normalizeValue($value);
        /** @var ChoiceTreeInput $input */
        $input = $this->getElement('GridFilterChoiceTreeValueInput');
        $input->checkValue($value, $isShouldSee);
    }
}
