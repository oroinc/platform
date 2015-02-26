<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures;

use Oro\Component\Layout\ContextInterface;
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

    /** @var array */
    protected $loadedDataProviders;

    public function __construct(
        $loadedTypes,
        $loadedTypeExtensions,
        $loadedLayoutUpdates,
        $loadedContextConfigurators,
        $loadedDataProviders
    ) {
        $this->loadedTypes                = $loadedTypes;
        $this->loadedTypeExtensions       = $loadedTypeExtensions;
        $this->loadedLayoutUpdates        = $loadedLayoutUpdates;
        $this->loadedContextConfigurators = $loadedContextConfigurators;
        $this->loadedDataProviders        = $loadedDataProviders;
    }

    protected function loadTypes()
    {
        return $this->loadedTypes;
    }

    protected function loadTypeExtensions()
    {
        return $this->loadedTypeExtensions;
    }

    protected function loadLayoutUpdates(ContextInterface $context)
    {
        return $this->loadedLayoutUpdates;
    }

    protected function loadContextConfigurators()
    {
        return $this->loadedContextConfigurators;
    }

    protected function loadDataProviders()
    {
        return $this->loadedDataProviders;
    }
}
