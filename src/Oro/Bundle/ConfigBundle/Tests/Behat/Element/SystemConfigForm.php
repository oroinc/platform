<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;

class SystemConfigForm extends Form
{
    /**
     * @param TableNode $table
     */
    public function fill(TableNode $table)
    {
        foreach ($table->getRows() as list($label, $value)) {
            $value = self::normalizeValue($value);

            if ($this->isUseDefaultCheckboxExists($label)) {
                $this->uncheckCheckboxByLabel($label, 'Use default');
            }

            $input = $this->getSettingControlByLabel($label);

            $input->setValue($value);
        }
    }

    /**
     * @param string $label
     * @param string $checkbox
     * @throws ElementNotFoundException
     */
    public function checkCheckboxByLabel($label, $checkbox)
    {
        $this->getSettingControlByLabel($label, $checkbox)->check();
    }

    /**
     * @param string $label
     * @param string $checkbox
     * @param null|string $section
     * @throws ElementNotFoundException
     */
    public function uncheckCheckboxByLabel($label, $checkbox, $section = null)
    {
        $this->getSettingControlByLabel($label, $checkbox, $section)->uncheck();
    }

    /**
     * Retrieve option input object by setting label
     *
     * @param string $label
     * @param null|string $inputLabel
     * @param null|string $section
     * @return NodeElement|null
     * @throws ElementNotFoundException
     */
    private function getSettingControlByLabel($label, $inputLabel = null, $section = null)
    {
        $container = $this->getContainer($label, $section);

        if ($inputLabel != null) {
            $useDefaultLabel = $container->find('css', "label:contains('$inputLabel')");
            self::assertNotNull($useDefaultLabel, "Use default checkbox element for $label not found");
            $useDefaultLabel->focus();
            $input = $useDefaultLabel->getParent()->find('css', 'input');
        } else {
            $input = $container->find('css', '[data-name="field__value"]');
        }

        $colorsBlock = $container->find('css', '.simplecolorpicker');
        if ($input->getAttribute('type') == 'hidden' && !empty($colorsBlock)) {
            $input = $this->elementFactory->wrapElement('ColorsConfigBlock', $colorsBlock->getParent());
        }

        self::assertNotNull($input, "Input element for $label not found");

        return $input;
    }

    /**
     * @param string $label
     * @param null|string $section
     * @return NodeElement
     */
    private function getContainer($label, $section = null)
    {
        if ($section) {
            $labelElement = $this->find(
                'xpath',
                "//h5/span[text()=\"$section\"]/../..//label[text()=\"$label\"]"
            );
        } else {
            $labelElement = $this->find('css', "label:contains('$label')");
        }

        self::assertNotNull($labelElement, "Label element for $label not found");

        return $labelElement->getParent()->getParent();
    }

    /**
     * @param string $label
     * @param string $name
     * @return bool
     */
    private function isUseDefaultCheckboxExists($label, $name = 'Use default')
    {
        $container = $this->getContainer($label);

        return $container->find('css', "label:contains('$name')") !== null;
    }
}
