<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\Options;

/**
 * Configures the layout context with application debug mode setting.
 *
 * This configurator registers the `debug` context variable, which reflects the
 * current application's debug mode status. If not explicitly provided, it defaults
 * to the kernel's debug mode, allowing layouts and blocks to adapt their behavior
 * based on whether the application is running in debug or production mode.
 */
class ApplicationContextConfigurator implements ContextConfiguratorInterface
{
    /** @var KernelInterface */
    protected $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    #[\Override]
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
            ->setAllowedTypes('debug', 'bool');
    }
}
