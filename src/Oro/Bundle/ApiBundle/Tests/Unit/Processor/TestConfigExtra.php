<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

class TestConfigExtra implements ConfigExtraInterface
{
    private string $name;
    private array $contextAttributes;

    public function __construct(string $name, array $contextAttributes = [])
    {
        $this->name = $name;
        $this->contextAttributes = $contextAttributes;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function configureContext(ConfigContext $context): void
    {
        foreach ($this->contextAttributes as $name => $value) {
            $context->set($name, $value);
        }
    }

    #[\Override]
    public function isPropagable(): bool
    {
        return false;
    }

    #[\Override]
    public function getCacheKeyPart(): ?string
    {
        return null;
    }
}
