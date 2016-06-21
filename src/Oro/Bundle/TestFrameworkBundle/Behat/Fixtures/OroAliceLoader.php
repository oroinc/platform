<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Nelmio\Alice\Fixtures\Loader;
use Nelmio\Alice\Instances\Collection as AliceCollection;

class OroAliceLoader extends Loader
{
    /**
     * @return AliceCollection
     */
    public function getReferenceRepository()
    {
        return $this->objects;
    }
}
