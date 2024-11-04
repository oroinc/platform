<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

class TestConfigSection implements ConfigExtraSectionInterface
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function configureContext(ConfigContext $context): void
    {
    }

    #[\Override]
    public function isPropagable(): bool
    {
        return true;
    }

    #[\Override]
    public function getConfigType(): string
    {
        return $this->name;
    }

    #[\Override]
    public function getCacheKeyPart(): ?string
    {
        return null;
    }
}
