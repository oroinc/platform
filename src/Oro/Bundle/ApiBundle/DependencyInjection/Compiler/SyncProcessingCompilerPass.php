<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configures services related to synchronous processing pf batch operations.
 */
class SyncProcessingCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $config = DependencyInjectionUtil::getConfig($container);
        $batchConfig = $config['batch_api'];

        $container->getDefinition('oro_api.update_list.process_synchronous_operation')
            ->setArgument('$waitTimeout', $batchConfig['sync_processing_wait_timeout']);

        $container->getDefinition('oro_api.batch.sync_processing_limit_provider')
            ->setArgument('$defaultLimit', $batchConfig['sync_processing_limit'])
            ->setArgument('$entityLimits', $batchConfig['sync_processing_limit_per_entity'])
            ->setArgument('$defaultIncludedDataLimit', $batchConfig['sync_processing_included_data_limit'])
            ->setArgument('$entityIncludedDataLimits', $batchConfig['sync_processing_included_data_limit_per_entity']);
    }
}
