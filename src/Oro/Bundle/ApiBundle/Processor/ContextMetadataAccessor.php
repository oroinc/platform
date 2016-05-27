<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;

class ContextMetadataAccessor implements MetadataAccessorInterface
{
    /** @var Context */
    protected $context;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($className)
    {
        return $this->context->getClassName() === $className
            ? $this->context->getMetadata()
            : null;
    }
}
