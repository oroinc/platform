<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SearchConfigurationPass implements CompilerPassInterface
{
    const SEARCH_CONFIG = 'oro_search.entities_config';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $configData = $container->getParameter(self::SEARCH_CONFIG);
        foreach (array_keys($configData) as $className) {
            $configData[$className]['fields'][] =
                [
                    'name' => 'organization',
                    'target_type' => 'integer',
                    'target_fields' => ['organization']
                ];
        }

        $container->setParameter(self::SEARCH_CONFIG, $configData);
    }
}
