<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Symfony\Component\HttpKernel\KernelInterface;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;

class ApplicationContextConfigurator implements ContextConfiguratorInterface
{
    /** @var KernelInterface */
    protected $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getDataResolver()->setOptional(['debug']);
        $context->set('debug', $this->kernel->isDebug());
    }
}
