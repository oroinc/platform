<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

/**
 * Defines the contract for classes that need to be aware of the fixture loader.
 *
 * Classes implementing this interface can be injected with a {@see FixtureLoader} to load
 * test fixtures during Behat scenario execution.
 */
interface FixtureLoaderAwareInterface
{
    public function setFixtureLoader(FixtureLoader $fixtureLoader);
}
