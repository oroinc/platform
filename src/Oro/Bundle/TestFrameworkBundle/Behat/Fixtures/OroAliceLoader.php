<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Nelmio\Alice\Fixtures\Loader;
use Nelmio\Alice\Instances\Collection;

class OroAliceLoader extends Loader
{
    /**
     * @return Collection
     */
    public function getReferenceRepository()
    {
        return $this->objects;
    }
}
