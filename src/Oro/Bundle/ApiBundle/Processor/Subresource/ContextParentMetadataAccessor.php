<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;

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
    public function getMetadata($className)
    {
        return $this->context->getParentClassName() === $className
            ? $this->context->getParentMetadata()
            : null;
    }
}
