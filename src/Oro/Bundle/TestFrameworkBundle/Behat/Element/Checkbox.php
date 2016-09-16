<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

class Checkbox extends Element
{
    public function setValue($value)
    {
        if ('false' === $value) {
            $this->uncheck();
        } else {
            $this->check();
        }
    }
}
