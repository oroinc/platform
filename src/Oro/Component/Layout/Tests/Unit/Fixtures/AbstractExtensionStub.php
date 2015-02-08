<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures;

use Oro\Component\Layout\AbstractExtension;

class AbstractExtensionStub extends AbstractExtension
{
    /** @var array */
    protected $loadedTypes;

    /** @var array */
    protected $loadedTypeExtensions;

    /** @var array */
    protected $loadedLayoutUpdates;

    public function __construct($loadedTypes, $loadedTypeExtensions, $loadedLayoutUpdates)
    {
        $this->loadedTypes          = $loadedTypes;
        $this->loadedTypeExtensions = $loadedTypeExtensions;
        $this->loadedLayoutUpdates  = $loadedLayoutUpdates;
    }

    protected function loadTypes()
    {
        return $this->loadedTypes;
    }

    protected function loadTypeExtensions()
    {
        return $this->loadedTypeExtensions;
    }

    protected function loadLayoutUpdates()
    {
        return $this->loadedLayoutUpdates;
    }

}
