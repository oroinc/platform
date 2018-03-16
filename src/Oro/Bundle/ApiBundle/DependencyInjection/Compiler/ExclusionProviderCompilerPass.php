<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all entity exclusion providers that are used only in Data API.
 */
class ExclusionProviderCompilerPass implements CompilerPassInterface
{
    private const EXCLUSION_PROVIDER_SERVICE_ID = 'oro_api.entity_exclusion_provider.shared';
    private const EXCLUSION_PROVIDER_TAG        = 'oro_entity.exclusion_provider.api';

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
