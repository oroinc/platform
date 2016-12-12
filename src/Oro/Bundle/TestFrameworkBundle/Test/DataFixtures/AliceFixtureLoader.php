<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Nelmio\Alice\Fixtures\Loader as AliceLoader;
use Nelmio\Alice\Instances\Collection as AliceReferenceRepository;

class AliceFixtureLoader extends AliceLoader
{
    /**
     * @return AliceReferenceRepository
     */
    public function getReferenceRepository()
    {
        return $this->objects;
    }
}
