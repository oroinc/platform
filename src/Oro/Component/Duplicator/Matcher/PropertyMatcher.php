<?php

declare(strict_types=1);

namespace Oro\Component\Duplicator\Matcher;

use Oro\Component\Duplicator\PropertyBagInterface;

/**
 * Matches a property by the owner class and the property name; the owner of a property bag is the bag's owner class.
 */
class PropertyMatcher implements Matcher
{
    public function __construct(
        private readonly string $class,
        private readonly string $property
    ) {
    }

    #[\Override]
    public function matches($object, $property): bool
    {
        if ($property !== $this->property) {
            return false;
        }

        if ($object instanceof PropertyBagInterface) {
            return is_a($object->getOwnerClass(), $this->class, true);
        }

        return $object instanceof $this->class;
    }
}
