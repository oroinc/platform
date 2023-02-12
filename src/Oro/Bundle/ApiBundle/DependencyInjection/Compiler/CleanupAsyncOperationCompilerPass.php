<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Command\CleanupAsyncOperationsCommand;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configures the asynchronous operations cleanup command.
 */
class CleanupAsyncOperationCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $config = DependencyInjectionUtil::getConfig($container);
        $asyncOperationConfig = $config['batch_api']['async_operation'];

        $container->getDefinition(CleanupAsyncOperationsCommand::class)
            ->replaceArgument(0, $asyncOperationConfig['lifetime'])
            ->replaceArgument(1, $asyncOperationConfig['cleanup_process_timeout'])
            ->replaceArgument(2, $asyncOperationConfig['operation_timeout']);
    }
}
