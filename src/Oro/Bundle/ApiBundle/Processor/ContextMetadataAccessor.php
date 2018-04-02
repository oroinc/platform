<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;

/**
 * Provides the metadata of API resource from the execution context
 * of processors responsible for primary resource actions.
 */
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
    public function getMetadata(string $className): ?EntityMetadata
    {
        return \is_a($this->context->getClassName(), $className, true)
            ? $this->context->getMetadata()
            : null;
    }
}
