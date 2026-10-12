<?php

declare(strict_types=1);

namespace Oro\Component\Duplicator\Matcher;

use Oro\Component\Duplicator\PropertyBagInterface;

/**
 * Matches a property bag entry that holds an object.
 */
class PropertyBagObjectMatcher implements Matcher
{
    #[\Override]
    public function matches($object, $property): bool
    {
        if (!$object instanceof PropertyBagInterface) {
            return false;
        }

        $reflection = new \ReflectionObject($object);

        return $reflection->hasProperty($property)
            && \is_object($reflection->getProperty($property)->getValue($object));
    }
}
