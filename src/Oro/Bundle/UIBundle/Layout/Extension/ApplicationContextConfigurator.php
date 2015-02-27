<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

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
        $context->getDataResolver()
            ->setDefaults(['debug' => null])
            ->setAllowedTypes(['debug' => 'bool'])
            ->setNormalizers(
                [
                    'debug' => function (Options $options, $debug) {
                        if (is_null($debug)) {
                            $debug = $this->kernel->isDebug();
                        }

                        return $debug;
                    }
                ]
            );
    }
}
