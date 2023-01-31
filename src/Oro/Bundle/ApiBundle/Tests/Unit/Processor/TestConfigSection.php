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
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigType(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart(): ?string
    {
        return null;
    }
}
