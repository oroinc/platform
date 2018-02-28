<?php

namespace Oro\Bundle\MigrationBundle\Locator;

use Oro\Bundle\MigrationBundle\Migration\DataFixturesExecutorInterface;

/**
 * Provide information about fixture path
 *
 * @package Oro\Bundle\MigrationBundle\Locator
 */
class FixturePathLocator implements FixturePathLocatorInterface
{
    protected const MAIN_FIXTURES_PATH = 'Migrations/Data/ORM';
    protected const DEMO_FIXTURES_PATH = 'Migrations/Data/Demo/ORM';

    /**
     * {@inheritdoc}
     */
    public function getPath(string $fixtureType): string
    {
        if ($fixtureType === DataFixturesExecutorInterface::DEMO_FIXTURES) {
            return self::DEMO_FIXTURES_PATH;
        }

        return self::MAIN_FIXTURES_PATH;
    }
}
