<?php

namespace Oro\Bundle\MigrationBundle\Locator;

/**
 * Give interface for Path Locators.
 *
 * @package Oro\Bundle\MigrationBundle\Locator
 */
interface FixturePathLocatorInterface
{
    /**
     * @param string $fixtureType
     *
     * @return string
     */
    public function getPath(string $fixtureType): string;
}
