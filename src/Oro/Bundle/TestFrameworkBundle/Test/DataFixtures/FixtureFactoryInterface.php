<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

/**
 * Defines the contract for creating fixture instances from identifiers.
 *
 * Implementations of this interface create fixture objects from fixture identifiers,
 * which can be class names, file paths, or other fixture references.
 */
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
