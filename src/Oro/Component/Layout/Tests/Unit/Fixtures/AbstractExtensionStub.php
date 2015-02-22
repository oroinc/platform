<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures;

use Oro\Component\Layout\Extension\AbstractExtension;

class AbstractExtensionStub extends AbstractExtension
{
    /** @var array */
    protected $loadedTypes;

    /** @var array */
    protected $loadedTypeExtensions;

    /** @var array */
    protected $loadedLayoutUpdates;

    /** @var array */
    protected $loadedContextConfigurators;

    public function __construct(
        $loadedTypes,
        $loadedTypeExtensions,
        $loadedLayoutUpdates,
        $loadedContextConfigurators
    ) {
        $this->loadedTypes                = $loadedTypes;
        $this->loadedTypeExtensions       = $loadedTypeExtensions;
        $this->loadedLayoutUpdates        = $loadedLayoutUpdates;
        $this->loadedContextConfigurators = $loadedContextConfigurators;
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

    protected function loadContextConfigurators()
    {
        return $this->loadedContextConfigurators;
    }
}
