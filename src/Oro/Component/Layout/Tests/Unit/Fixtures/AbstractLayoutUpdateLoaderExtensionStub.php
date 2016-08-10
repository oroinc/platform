<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures;

use Oro\Component\Layout\Extension\AbstractLayoutUpdateLoaderExtension;

class AbstractLayoutUpdateLoaderExtensionStub extends AbstractLayoutUpdateLoaderExtension
{
    /** @var array */
    protected $resources;

     /**
      * @param array $loadedResources
      */
    public function __construct($loadedResources)
    {
        $this->resources = $loadedResources;
    }
}
