<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface;

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
    public function getConfig($className)
    {
        return $this->context->getClassName() === $className
            ? $this->context->getConfig()
            : null;
    }
}
