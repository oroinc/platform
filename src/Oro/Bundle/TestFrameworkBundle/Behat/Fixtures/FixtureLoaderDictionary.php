<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

trait FixtureLoaderDictionary
{
    /**
     * @var FixtureLoader
     */
    protected $fixtureLoader;

    public function setFixtureLoader(FixtureLoader $fixtureLoader)
    {
        $this->fixtureLoader = $fixtureLoader;
    }
}
