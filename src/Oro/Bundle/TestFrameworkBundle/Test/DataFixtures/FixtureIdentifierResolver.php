<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

class FixtureIdentifierResolver implements FixtureIdentifierResolverInterface
{
    /**
     * {@inheritdoc}
     */
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
