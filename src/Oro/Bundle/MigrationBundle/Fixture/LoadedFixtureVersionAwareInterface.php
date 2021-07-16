<?php

namespace Oro\Bundle\MigrationBundle\Fixture;

interface LoadedFixtureVersionAwareInterface
{
    /**
     * Set current loaded fixture version
     */
    public function setLoadedVersion($version = null);
}
