<?php

namespace Oro\Bundle\BatchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class DebugTranslatorPass implements CompilerPassInterface
{
    const DEBUG_BATCH_PARAMETER = 'oro_batch.debug_batch';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($debugBatch = $container->getParameter(self::DEBUG_BATCH_PARAMETER)) {
            $container->get('akeneo_batch.logger_subscriber')->setIsActive($debugBatch);
        }
    }
}
