<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\FormBundle\Tests\Behat\Element\AllowedColorsMapping;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;

class ColorsConfigBlock extends Form
{
    use AllowedColorsMapping;

    /** @var NodeElement */
    private $colorsStoreElement;

    protected function init()
    {
        $this->colorsStoreElement = $this->find('css', "input[type='hidden']");
        self::assertNotNull($this->colorsStoreElement, "Hidden input which store current colors not found");
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($values)
    {
        $values = $this->parseValues($values);
        $currentValues = $this->getColorValues();

        foreach ($values as $key => $value) {
            $currentValues[$key] = $this->getHexByColorName($value);
        }

        $this->setColorValues($currentValues);
    }

    /**
     * @return mixed
     */
    protected function getColorValues()
    {
        return json_decode($this->colorsStoreElement->getValue());
    }

    /**
     * Replaced first items with new colors and write result to hidden input element
     *
     * @param array $values
     */
    protected function setColorValues($values)
    {
        $xpath = $this->colorsStoreElement->getXpath();
        $encodedValues = json_encode($values);
        $script = <<<JS
var element = document.evaluate("{$xpath}", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue; 
element.value = '{$encodedValues}';
JS;

        $this->getDriver()->executeScript($script);
        $this->getDriver()->waitForAjax();
    }

    /**
     * @param string $valueString
     * @return array
     */
    private function parseValues($valueString)
    {
        return array_map('trim', explode(',', $valueString));
    }
}
