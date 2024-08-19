<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configures the provider that responsible to get the maximum number of objects
 * that can be saved in one batch operation chunk.
 */
class ChunkSizeProviderCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $config = DependencyInjectionUtil::getConfig($container);
        $batchConfig = $config['batch_api'];

        $container->getDefinition('oro_api.batch.chunk_size_provider')
            ->setArgument('$defaultChunkSize', $batchConfig['chunk_size'])
            ->setArgument('$entityChunkSizes', $batchConfig['chunk_size_per_entity'])
            ->setArgument('$defaultIncludedDataChunkSize', $batchConfig['included_data_chunk_size'])
            ->setArgument('$entityIncludedDataChunkSizes', $batchConfig['included_data_chunk_size_per_entity']);
    }
}
