<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContextAggregatorCompilerPass implements CompilerPassInterface
{
    const AGGREGATOR_REGISTRY_SERVICE_ID = 'oro_importexport.job.context.aggregator_registry';
    const AGGREGATOR_TAG                 = 'oro_importexport.job.context.aggregator';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        DependencyInjectionUtil::registerTaggedServices(
            $container,
            self::AGGREGATOR_REGISTRY_SERVICE_ID,
            self::AGGREGATOR_TAG,
            'addAggregator'
        );
    }
}
