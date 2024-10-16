<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Metadata\Extra\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;

class TestMetadataExtra implements MetadataExtraInterface
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
    public function configureContext(MetadataContext $context): void
    {
    }

    #[\Override]
    public function getCacheKeyPart(): ?string
    {
        return null;
    }
}
