<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * Provides the configuration of parent API resource from the execution context
 * of processors responsible for subresources and relationships.
 */
class ContextParentConfigAccessor implements ConfigAccessorInterface
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
    public function getConfig(string $className): ?EntityDefinitionConfig
    {
        return \is_a($this->context->getParentClassName(), $className, true)
            ? $this->context->getParentConfig()
            : null;
    }
}
