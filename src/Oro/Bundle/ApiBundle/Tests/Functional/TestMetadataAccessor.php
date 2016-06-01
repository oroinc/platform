<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;

class TestMetadataAccessor implements MetadataAccessorInterface
{
    /** @var EntityMetadata[] */
    protected $metadata = [];

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
    public function getMetadata($className)
    {
        return isset($this->metadata[$className])
            ? $this->metadata[$className]
            : null;
    }
}
