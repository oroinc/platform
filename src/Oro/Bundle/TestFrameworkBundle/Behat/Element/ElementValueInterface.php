<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Driver\DriverInterface;

/**
 * Defines the contract for element value representations that can be set and converted to strings.
 *
 * Implementations of this interface represent values that can be applied to page elements
 * via XPath and a Mink driver, and can be converted to string representations for comparison
 * and assertion purposes.
 */
interface ElementValueInterface
{
    /**
     * Set current ElementValueInterface state to element value
     *
     * @param string $xpath
     * @param DriverInterface $driver
     * @return void
     */
    public function set($xpath, DriverInterface $driver);

    /**
     * @return string
     */
    public function __toString();
}
