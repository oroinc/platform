<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DataCollectorCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $dataCollectorDefinition = $container->getDefinition('oro_entity.profiler.duplicate_queries_data_collector');

        $connectionNames = $container->get('doctrine')->getConnectionNames();

        foreach ($connectionNames as $name => $serviceId) {
            $loggerId = 'doctrine.dbal.logger.profiling.'.$name;
            if ($container->has($loggerId)) {
                $dataCollectorDefinition->addMethodCall('addLogger', [
                    $name,
                    new Reference($loggerId)
                ]);
            }
        }
    }
}
