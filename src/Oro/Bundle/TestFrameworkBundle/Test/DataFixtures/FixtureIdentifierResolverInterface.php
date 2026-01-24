<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

/**
 * Defines the contract for resolving unique identifiers for fixtures.
 *
 * Implementations of this interface convert fixture objects and strings to unique
 * string identifiers for tracking and managing fixtures during test execution.
 */
interface FixtureIdentifierResolverInterface
{
    /**
     * Returns a string that uniquely identifies a given fixture.
     *
     * @param mixed $fixture
     *
     * @return string
     */
    public function resolveId($fixture);
}
