<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

/**
 * Checkbox element implementation
 */
class Checkbox extends Element
{
    /**
     * @param array|bool|string $value
     */
    public function setValue($value)
    {
        if (in_array($value, [false, 'false', 'uncheck', 'unselect'], true)) {
            $this->uncheck();
        } else {
            $this->check();
        }
    }
}
