<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

interface FixtureFactoryInterface
{
    /**
     * Creates a fixture instance by its identifier.
     *
     * @param string $fixtureId
     *
     * @return object
     */
    public function createFixture($fixtureId);
}
