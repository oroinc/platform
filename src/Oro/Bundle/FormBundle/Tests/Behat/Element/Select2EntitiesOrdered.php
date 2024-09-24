<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

/**
 * Select2Entities implementation that clears values before setting them, should be used for elements
 * where order matters.
 */
class Select2EntitiesOrdered extends Select2Entities
{
    #[\Override]
    protected function clearExcept(array $values = [])
    {
        parent::clearExcept([]);
    }

    #[\Override]
    protected function hasValue(string $value): bool
    {
        return false;
    }
}
