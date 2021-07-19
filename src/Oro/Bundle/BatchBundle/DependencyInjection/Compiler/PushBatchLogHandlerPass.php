<?php

namespace Oro\Bundle\BatchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds the batch handler into the batch logger.
 */
class PushBatchLogHandlerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('monolog.logger.batch')) {
            return;
        }

        $container
            ->getDefinition('monolog.logger.batch')
            ->addMethodCall('pushHandler', [new Reference('oro_batch.monolog.handler.batch_log_handler')]);
    }
}
