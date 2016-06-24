<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

interface FixtureLoaderAwareInterface
{
    /**
     * @param FixtureLoader $fixtureLoader
     */
    public function setFixtureLoader(FixtureLoader $fixtureLoader);
}
