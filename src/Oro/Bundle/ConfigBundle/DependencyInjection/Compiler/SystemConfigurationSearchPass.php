<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SystemConfigurationSearchPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    const EXTENSION_TAG = 'oro_config.configuration_search_provider';
    const SERVICE_ID = 'oro_config.configuration_search_provider.chain';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices($container, self::SERVICE_ID, self::EXTENSION_TAG, 'addProvider');
    }
}
