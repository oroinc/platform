<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

class TypeableInput extends Element
{
    #[\Override]
    public function setValue($value)
    {
        parent::setValue(new InputValue(InputMethod::TYPE, $value));
    }
}
