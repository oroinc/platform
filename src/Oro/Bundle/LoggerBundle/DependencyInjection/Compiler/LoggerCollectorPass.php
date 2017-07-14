<?php

namespace Oro\Bundle\LoggerBundle\DependencyInjection\Compiler;

use Oro\Bundle\LoggerBundle\DataCollector\LoggerDataCollector;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LoggerCollectorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('data_collector.logger')) {
            $definition = $container->getDefinition('data_collector.logger');
            $definition->setClass(LoggerDataCollector::class);
        }
    }
}
