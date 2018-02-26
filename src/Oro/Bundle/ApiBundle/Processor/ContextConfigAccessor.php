<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * Provides the configuration of API resource from the execution context
 * of processors responsible for primary resource actions.
 */
class ContextConfigAccessor implements ConfigAccessorInterface
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
    public function getConfig(string $className): ?EntityDefinitionConfig
    {
        return \is_a($this->context->getClassName(), $className, true)
            ? $this->context->getConfig()
            : null;
    }
}
