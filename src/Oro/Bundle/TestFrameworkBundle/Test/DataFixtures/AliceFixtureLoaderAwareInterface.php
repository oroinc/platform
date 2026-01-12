<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

/**
 * Defines the contract for classes that need to be aware of the Alice fixture loader.
 *
 * Classes implementing this interface can be injected with an {@see AliceFixtureLoader}
 * to load Alice fixtures during functional test execution.
 */
interface AliceFixtureLoaderAwareInterface
{
    public function setLoader(AliceFixtureLoader $loader);
}
