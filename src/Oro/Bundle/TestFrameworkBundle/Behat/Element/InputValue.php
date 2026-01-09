<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Driver\DriverInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;

/**
 * Represents a value to be input into a form element using a specified input method.
 *
 * This class encapsulates a value and the method to use for inputting it (TYPE for keyboard input
 * or SET for direct JavaScript value assignment), allowing flexible value setting strategies
 * for different types of form elements.
 */
class InputValue implements ElementValueInterface
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var string TYPE|SET
     */
    private $inputMethod;

    /**
     * @param string $inputMethod
     * @param string $value
     */
    public function __construct($inputMethod, $value)
    {
        $this->value = $value;
        $this->inputMethod = $inputMethod;
    }

    /**
     * @param string $xpath
     * @param OroSelenium2Driver $driver
     */
    #[\Override]
    public function set($xpath, DriverInterface $driver)
    {
        if (InputMethod::SET === $this->inputMethod) {
            $script = <<<JS
            var node = {{ELEMENT}};
            node.value = '$this->value';
JS;
            $driver->executeJsOnXpath($xpath, $script);
        } elseif (InputMethod::TYPE === $this->inputMethod) {
            $driver->typeIntoInput($xpath, $this->value);
        } else {
            throw new \RuntimeException('Unsupported input method');
        }
    }

    #[\Override]
    public function __toString()
    {
        return (string)$this->value;
    }
}
