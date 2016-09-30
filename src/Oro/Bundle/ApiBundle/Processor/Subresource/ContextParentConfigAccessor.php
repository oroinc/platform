<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface;

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
    public function getConfig($className)
    {
        return $this->context->getParentClassName() === $className
            ? $this->context->getParentConfig()
            : null;
    }
}
