<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures;

use Oro\Component\Layout\Extension\AbstractExtension;
use Oro\Component\Layout\LayoutItemInterface;

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

    #[\Override]
    protected function loadTypes()
    {
        return $this->loadedTypes;
    }

    #[\Override]
    protected function loadTypeExtensions()
    {
        return $this->loadedTypeExtensions;
    }

    #[\Override]
    protected function loadLayoutUpdates(LayoutItemInterface $item)
    {
        return $this->loadedLayoutUpdates;
    }

    #[\Override]
    protected function loadContextConfigurators()
    {
        return $this->loadedContextConfigurators;
    }

    #[\Override]
    protected function loadDataProviders()
    {
        return $this->loadedDataProviders;
    }
}
