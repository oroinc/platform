<?php

namespace Oro\Bundle\ReportBundle\DependencyInjection\Compiler;

use Oro\Bundle\ReportBundle\Grid\ReportQueryExecutor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures services required to use separate DBAL connection to execute report queries.
 */
class DbalConnectionCompilerPass implements CompilerPassInterface
{
    public const CONNECTION_PARAM_NAME        = 'oro_report.dbal.connection';
    public const DATAGRID_PREFIXES_PARAM_NAME = 'oro_report.dbal.datagrid_prefixes';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasParameter(self::CONNECTION_PARAM_NAME)) {
            $this->decorateDatagridQueryExecutor(
                $container,
                $container->getParameter(self::CONNECTION_PARAM_NAME),
                $container->getParameter(self::DATAGRID_PREFIXES_PARAM_NAME)
            );
            $container->getParameterBag()->remove(self::CONNECTION_PARAM_NAME);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $reportConnectionName
     * @param string[]         $reportDatagridPrefixes
     */
    private function decorateDatagridQueryExecutor(
        ContainerBuilder $container,
        string $reportConnectionName,
        array $reportDatagridPrefixes
    ): void {
        if (!$container->hasDefinition(sprintf('doctrine.dbal.%s_connection', $reportConnectionName))) {
            throw new InvalidArgumentException(sprintf(
                'The "%s" DBAL connection specified for "oro_report.connection" is not configured.',
                $reportConnectionName
            ));
        }

        $container
            ->register('oro_report.datagrid_orm_query_executor', ReportQueryExecutor::class)
            ->setArguments([
                new Reference('.inner'),
                new Reference('doctrine'),
                $reportConnectionName,
                $reportDatagridPrefixes
            ])
            ->setDecoratedService('oro_datagrid.orm.query_executor')
            ->setPublic(false);
    }
}
