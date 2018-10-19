<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;

class TestMetadataAccessor implements MetadataAccessorInterface
{
    /** @var EntityMetadata[] */
    private $metadata = [];

    /**
     * @param EntityMetadata[] $metadata
     */
    public function __construct(array $metadata)
    {
        foreach ($metadata as $item) {
            $this->metadata[$item->getClassName()] = $item;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(string $className): ?EntityMetadata
    {
        return $this->metadata[$className] ?? null;
    }
}
