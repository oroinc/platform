<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

interface AliceFixtureLoaderAwareInterface
{
    public function setLoader(AliceFixtureLoader $loader);
}
