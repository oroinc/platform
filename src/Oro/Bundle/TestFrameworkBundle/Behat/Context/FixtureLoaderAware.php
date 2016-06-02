<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

interface FixtureLoaderAware
{
    /**
     * @param FixtureLoader $fixtureLoader
     *
     * @return null
     */
    public function setFixtureLoader(FixtureLoader $fixtureLoader);
}
