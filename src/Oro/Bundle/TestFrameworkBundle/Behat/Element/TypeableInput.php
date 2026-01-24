<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

/**
 * Represents an input element that accepts values via keyboard typing.
 *
 * This element wraps values in an {@see InputValue} with the TYPE method, ensuring that
 * values are set by simulating keyboard input rather than direct JavaScript assignment.
 */
class TypeableInput extends Element
{
    #[\Override]
    public function setValue($value)
    {
        parent::setValue(new InputValue(InputMethod::TYPE, $value));
    }
}
