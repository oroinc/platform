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

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ConfigContext $context): void
    {
        foreach ($this->contextAttributes as $name => $value) {
            $context->set($name, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagable(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart(): ?string
    {
        return null;
    }
}
