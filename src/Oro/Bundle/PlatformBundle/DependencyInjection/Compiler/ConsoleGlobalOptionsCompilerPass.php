<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConsoleGlobalOptionsCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;
    
    const PROVIDER_REGISTRY = 'oro_platform.provider.console.global_options_provider_registry';
    const PROVIDER_TAG = 'oro_platform.console.global_options_provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices(
            $container,
            self::PROVIDER_REGISTRY,
            self::PROVIDER_TAG,
            'registerProvider'
        );
    }
}
