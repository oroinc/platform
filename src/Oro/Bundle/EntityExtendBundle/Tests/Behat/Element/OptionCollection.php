<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\CollectionField;

class OptionCollection extends CollectionField
{
    /**
     * @param array $values
     */
    public function setValue($values)
    {
        $existingRows = $this->findAll('css', '.oro-multiselect-holder');
        $existingRowsCount = count($existingRows);

        $this->addNewRows($values);
        $rows = $this->findAll('css', '.oro-multiselect-holder');

        foreach ($values as $key => $value) {
            /** @var NodeElement $row */
            $row = $rows[$existingRowsCount + $key];
            $rowNumber = $row->getParent()->getAttribute('data-content');

            $label = sprintf('//input[contains(@id,"options_%s_label")]', $rowNumber);

            $row->find('xpath', $label)->setValue($value['Label']);
        }
    }
}
