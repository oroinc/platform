<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds request types for all registered API views to the API resources cache warmer.
 */
class ResourcesCacheWarmerCompilerPass implements CompilerPassInterface
{
    private const RESOURCES_CACHE_WARMER_SERVICE = 'oro_api.resources.cache_warmer';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $requestTypes = [];
        $views = $this->getApiDocViews($container);
        foreach ($views as $view) {
            if (!empty($view['request_type'])) {
                $requestTypes[] = $view['request_type'];
            }
        }
        $container->getDefinition(self::RESOURCES_CACHE_WARMER_SERVICE)
            ->replaceArgument(2, $requestTypes);
    }

    private function getApiDocViews(ContainerBuilder $container): array
    {
        $config = DependencyInjectionUtil::getConfig($container);

        return $config['api_doc_views'];
    }
}
