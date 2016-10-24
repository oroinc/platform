<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Element\ElementInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;

class InputValue implements ElementValueInterface
{
    const TYPE = 'type';
    const SET  = 'set';

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
    public function set($xpath, DriverInterface $driver)
    {
        if (self::SET === $this->inputMethod) {
            $script = <<<JS
            var node = {{ELEMENT}};
            node.value = '$this->value';
JS;
            $driver->executeJsOnXpath($xpath, $script);
        } elseif (self::TYPE === $this->inputMethod) {
            $driver->typeIntoInput($xpath, $this->value);
        } else {
            throw new \RuntimeException('Unsupported input method');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->value;
    }
}
