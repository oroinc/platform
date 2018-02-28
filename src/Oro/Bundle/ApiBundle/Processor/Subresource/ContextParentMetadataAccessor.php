<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;

/**
 * Provides the metadata of parent API resource from the execution context
 * of processors responsible for subresources and relationships.
 */
class ContextParentMetadataAccessor implements MetadataAccessorInterface
{
    /** @var SubresourceContext */
    protected $context;

    /**
     * @param SubresourceContext $context
     */
    public function __construct(SubresourceContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(string $className): ?EntityMetadata
    {
        return \is_a($this->context->getParentClassName(), $className, true)
            ? $this->context->getParentMetadata()
            : null;
    }
}
