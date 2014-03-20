<?php

namespace Oro\Bundle\MigrationBundle\Fixture;

interface VersionedFixtureInterface
{
    /**
     * Return current fixture version
     *
     * @return string
     */
    public function getVersion();
}
