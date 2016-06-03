<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

interface FixtureLoaderAwareInterface
{
    /**
     * @param FixtureLoader $fixtureLoader
     */
    public function setFixtureLoader(FixtureLoader $fixtureLoader);
}
