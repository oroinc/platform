<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

/**
 * Factory for creating fixture instances from class names.
 *
 * This factory creates fixture instances by instantiating fixture classes,
 * validating that the class exists before instantiation.
 */
class FixtureFactory implements FixtureFactoryInterface
{
    #[\Override]
    public function createFixture($fixtureId)
    {
        if (!class_exists($fixtureId)) {
            throw new \InvalidArgumentException(sprintf('The class "%s" does not exist.', $fixtureId));
        }

        return new $fixtureId();
    }
}
