<?php

namespace Oro\Bundle\LoggerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds setSQLLogger() call to Doctrine DBAL Configuration
 * This restores SQL logging functionality using the deprecated but still working setSQLLogger() method
 */
class DoctrineSQLLoggerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ('test' === $container->getParameter('kernel.environment')) {
            return;
        }

        foreach ($container->getDefinitions() as $id => $definition) {
            // Looking for services like "doctrine.dbal.%connection_name%_connection.configuration"
            if (str_starts_with($id, 'doctrine.dbal.') && str_ends_with($id, '_connection.configuration')) {
                $this->addSQLLogger($container, $id);
            }
        }
    }

    private function addSQLLogger(ContainerBuilder $container, string $configurationServiceId): void
    {
        // Extract connection name from the service ID
        preg_match('/doctrine\.dbal\.(.+)_connection\.configuration/', $configurationServiceId, $matches);
        $connectionName = $matches[1] ?? 'default';

        $loggerServiceId = sprintf('oro_logger.doctrine.dbal.logger.%s', $connectionName);

        if (!$container->hasDefinition($loggerServiceId)) {
            $loggerServiceId = 'oro_logger.doctrine.dbal.logger';
        }

        if (!$container->hasDefinition($loggerServiceId) && !$container->hasAlias($loggerServiceId)) {
            return;
        }

        // Get Configuration definition and add setSQLLogger call
        $configDefinition = $container->getDefinition($configurationServiceId);
        $configDefinition->addMethodCall('setSQLLogger', [new Reference($loggerServiceId)]);
    }
}
