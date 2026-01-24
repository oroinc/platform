<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

/**
 * Resolves fixture identifiers from fixture objects and strings.
 *
 * This resolver converts fixture objects to their class names and passes through
 * string identifiers unchanged, providing a simple identification strategy.
 */
class FixtureIdentifierResolver implements FixtureIdentifierResolverInterface
{
    #[\Override]
    public function resolveId($fixture)
    {
        if (is_object($fixture)) {
            return get_class($fixture);
        }
        if (is_string($fixture)) {
            return $fixture;
        }

        throw new \InvalidArgumentException(
            sprintf('Expected argument of type "object or string", "%s" given.', gettype($fixture))
        );
    }
}
