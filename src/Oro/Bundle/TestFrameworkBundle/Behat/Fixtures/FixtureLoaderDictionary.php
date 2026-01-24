<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

/**
 * Provides functionality for storing and accessing a fixture loader instance.
 *
 * This trait implements the {@see FixtureLoaderAwareInterface}, allowing classes to maintain
 * a reference to a {@see FixtureLoader} for loading test fixtures during scenario execution.
 */
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
