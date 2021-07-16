<?php

namespace Oro\Bundle\MigrationBundle\Locator;

/**
 * Provides an interface for retrieving the path to data fixtures.
 */
interface FixturePathLocatorInterface
{
    public function getPath(string $fixtureType): string;
}
