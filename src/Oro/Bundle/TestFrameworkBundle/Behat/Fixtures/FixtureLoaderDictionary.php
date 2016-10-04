<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

trait FixtureLoaderDictionary
{
    /**
     * @var FixtureLoader
     */
    protected $fixtureLoader;

    /**
     * {@inheritdoc}
     */
    public function setFixtureLoader(FixtureLoader $fixtureLoader)
    {
        $this->fixtureLoader = $fixtureLoader;
    }
}
