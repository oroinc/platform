<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\Options;

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
        $context->getResolver()
            ->setDefaults(
                [
                    'debug' => function (Options $options, $value) {
                        if (null === $value) {
                            $value = $this->kernel->isDebug();
                        }

                        return $value;
                    }
                ]
            )
            ->setAllowedTypes(['debug' => 'bool']);
    }
}
