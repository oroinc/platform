<?php

namespace Oro\Bundle\MigrationBundle\Fixture;

/**
 * Should be implemented when a data fixture needs to know the last loaded version.
 */
interface LoadedFixtureVersionAwareInterface
{
    /**
     * Sets current loaded fixture version.
     */
    public function setLoadedVersion($version = null);
}
