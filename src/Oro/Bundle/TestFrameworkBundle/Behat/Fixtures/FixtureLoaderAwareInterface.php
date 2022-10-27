<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

interface FixtureLoaderAwareInterface
{
    public function setFixtureLoader(FixtureLoader $fixtureLoader);
}
