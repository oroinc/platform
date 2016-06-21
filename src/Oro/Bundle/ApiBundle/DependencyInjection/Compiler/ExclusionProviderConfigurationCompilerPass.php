<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;

class ExclusionProviderConfigurationCompilerPass implements CompilerPassInterface
{
    const EXCLUSION_PROVIDER_SERVICE_ID = 'oro_api.entity_exclusion_provider';
    const EXCLUSION_PROVIDER_TAG        = 'oro_entity.exclusion_provider.api';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        DependencyInjectionUtil::registerTaggedServices(
            $container,
            self::EXCLUSION_PROVIDER_SERVICE_ID,
            self::EXCLUSION_PROVIDER_TAG,
            'addProvider'
        );
    }
}
