<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;

class SystemConfigForm extends Form
{

    public function fill(TableNode $table)
    {
        foreach ($table->getRows() as list($label, $value)) {
            $value = self::normalizeValue($value);
            $this->uncheckUseDefaultCheckbox($label);
            $input = $this->getSettingControlByLabel($label);
            $input->setValue($value);
        }
    }

    /**
     * @param string $label
     * @throws ElementNotFoundException
     */
    public function uncheckUseDefaultCheckbox($label)
    {
        $this->getSettingControlByLabel($label, 'Use default')->uncheck();
    }

    /**
     * Retrieve option input object by setting label
     *
     * @param string $label
     * @param null|string $inputLabel
     * @return NodeElement|null
     */
    private function getSettingControlByLabel($label, $inputLabel = null)
    {
        $labelElement = $this->find('css', "label:contains('$label')");

        self::assertNotNull($labelElement, "Label element for $label not found");

        $container = $labelElement->getParent()->getParent();

        if ($inputLabel != null) {
            $useDefaultLabel = $container->find('css', "label:contains('$inputLabel')");
            $input = $useDefaultLabel->getParent()->find('css', 'input');
        } else {
            $input = $container->find('css', '.control-subgroup input');
        }

        self::assertNotNull($input, "Input element for $label not found");

        return $input;
    }
}
