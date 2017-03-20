<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Element;

use Behat\Mink\Session;
use Oro\Bundle\FormBundle\Tests\Behat\Element\AllowedColorsMapping;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory;

class ColorsConfigBlock extends Form
{
    use AllowedColorsMapping;

    private $colorsStoreElement;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        Session $session,
        OroElementFactory $elementFactory,
        $selector = ['type' => 'xpath', 'locator' => '/html/body']
    ) {
        parent::__construct($session, $elementFactory, $selector);

        $storeElem = $this->find('css', "input[type='hidden']");
        self::assertNotNull($storeElem, "Hidden input which store current colors not found");

        $this->colorsStoreElement = $storeElem;
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
        $this->getDriver()->waitForAjax(100);
    }

    /**
     * @param string $valueString
     * @return array
     */
    private function parseValues($valueString)
    {
        return explode(', ', $valueString);
    }
}
