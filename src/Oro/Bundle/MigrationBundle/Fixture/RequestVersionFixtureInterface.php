<?php

namespace Oro\Bundle\MigrationBundle\Fixture;

interface RequestVersionFixtureInterface
{
    /**
     * Set current db version
     *
     * @param $version
     */
    public function setDBVersion($version = null);
}
