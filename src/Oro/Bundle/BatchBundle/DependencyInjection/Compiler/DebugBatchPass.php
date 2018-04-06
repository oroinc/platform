<?php

namespace Oro\Bundle\BatchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DebugBatchPass implements CompilerPassInterface
{
    const LOG_BATCH_PARAMETER = 'oro_batch.log_batch';
    const BATCH_LOG_HANDLER   = 'akeneo_batch.logger.batch_log_handler';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::BATCH_LOG_HANDLER)) {
            $isDebugBatchEnabled = $container->getParameter(self::LOG_BATCH_PARAMETER);
            $container->getDefinition(self::BATCH_LOG_HANDLER)->addMethodCall('setIsActive', [$isDebugBatchEnabled]);
        }
    }
}
