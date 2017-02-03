<?php

namespace Oro\Bundle\UIBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class ControlGroup extends Element
{
    /**
     * If ControlLabel hold one value, it returns string
     * If ControlLabel hold several values it returns sorted array
     * If ControlLabel does not exists it returns null
     *
     * @return string|array|null
     */
    public function getValue()
    {
        if (!$this->isValid()) {
            return null;
        }

        $extraListLi = $this->findAll('css', 'ul.extra-list li');

        if (count($extraListLi)) {
            return array_map(function (NodeElement $li) {
                return $li->getText();
            }, $extraListLi);
        }

        return $this->getText();
    }

    /**
     * @param array|string $expectedValue
     * @return bool
     * @throws ElementNotFoundException
     */
    public function compareValues($expectedValue)
    {
        if (null === $actualValue = $this->getValue()) {
            throw new ElementNotFoundException(
                $this->getDriver(),
                'ControlGroup OroElement',
                'xpath',
                $this->getXpath()
            );
        }

        $expectedValueType = gettype($expectedValue);
        $actualValueType   = gettype($actualValue);

        if ($expectedValueType !== $actualValueType) {
            throw new \RuntimeException(sprintf(
                'Expected(%s) and Actual(%s) values has different types, and can\'t be compare',
                print_r($expectedValue, true),
                print_r($actualValue, true)
            ));
        }

        switch ($expectedValueType) {
            case 'string':
                return false !== stripos($actualValue, $expectedValue);
            case 'array':
                sort($expectedValue);
                sort($actualValue);

                return $expectedValue == $actualValue;
            default:
                return false;
        }
    }
}
