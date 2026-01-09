<?php

namespace Oro\Bundle\MigrationBundle\Fixture;

/**
 * Defines the contract for data fixtures that have version information.
 *
 * Fixtures implementing this interface can report their version, which is used by the migration
 * system to track which fixtures have been loaded and to manage fixture dependencies and ordering.
 */
interface VersionedFixtureInterface
{
    /**
     * Return current fixture version
     *
     * @return string
     */
    public function getVersion();
}
