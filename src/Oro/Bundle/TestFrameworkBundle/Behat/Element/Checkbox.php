<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

/**
 * Represents a checkbox form element in Behat tests.
 *
 * This element handles setting checkbox values, supporting various input formats
 * (boolean, string values like 'false', 'uncheck', 'unselect') and automatically
 * checking or unchecking the checkbox accordingly.
 */
class Checkbox extends Element
{
    #[\Override]
    public function setValue($value)
    {
        if (in_array($value, [false, 'false', 'uncheck', 'unselect'], true)) {
            $this->uncheck();
        } else {
            $this->check();
        }
    }
}
