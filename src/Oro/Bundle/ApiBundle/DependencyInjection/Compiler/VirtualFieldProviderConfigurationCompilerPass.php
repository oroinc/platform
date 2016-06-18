<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;

class VirtualFieldProviderConfigurationCompilerPass implements CompilerPassInterface
{
    const VIRTUAL_FIELD_PROVIDER_SERVICE_ID = 'oro_api.virtual_field_provider';
    const VIRTUAL_FIELD_PROVIDER_TAG        = 'oro_entity.virtual_field_provider.api';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        DependencyInjectionUtil::registerTaggedServices(
            $container,
            self::VIRTUAL_FIELD_PROVIDER_SERVICE_ID,
            self::VIRTUAL_FIELD_PROVIDER_TAG,
            'addProvider'
        );
    }
}
