<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

class FixtureFactory implements FixtureFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createFixture($fixtureId)
    {
        if (!class_exists($fixtureId)) {
            throw new \InvalidArgumentException(sprintf('The class "%s" does not exist.', $fixtureId));
        }

        return new $fixtureId();
    }
}
