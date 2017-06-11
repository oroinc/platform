<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

abstract class EntityPage extends Element
{
    /**
     * @param string $label
     * @param string $value
     */
    abstract public function assertPageContainsValue($label, $value);
}
