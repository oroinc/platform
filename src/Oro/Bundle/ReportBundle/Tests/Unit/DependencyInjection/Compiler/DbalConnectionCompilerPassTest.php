<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ReportBundle\DependencyInjection\Compiler\DbalConnectionCompilerPass;
use Oro\Bundle\ReportBundle\Grid\ReportQueryExecutor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DbalConnectionCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessWithoutReportDbalConnectionParameter()
    {
        $container = new ContainerBuilder();
        $container->register('oro_datagrid.orm.query_executor');

        $compiler = new DbalConnectionCompilerPass();
        $compiler->process($container);

        self::assertFalse($container->hasDefinition('oro_report.datagrid_orm_query_executor'));
    }

    public function testProcessWithReportDbalConnectionParameter()
    {
        $reportConnectionName = 'reports';
        $reportDatagridPrefixes = ['prefix1'];

        $container = new ContainerBuilder();
        $container->register('oro_datagrid.orm.query_executor');
        $container->register(sprintf('doctrine.dbal.%s_connection', $reportConnectionName));
        $container->setParameter(
            DbalConnectionCompilerPass::CONNECTION_PARAM_NAME,
            $reportConnectionName
        );
        $container->setParameter(
            DbalConnectionCompilerPass::DATAGRID_PREFIXES_PARAM_NAME,
            $reportDatagridPrefixes
        );

        $compiler = new DbalConnectionCompilerPass();
        $compiler->process($container);

        self::assertTrue($container->hasDefinition('oro_report.datagrid_orm_query_executor'));
        $decoratorDef = $container->getDefinition('oro_report.datagrid_orm_query_executor');
        self::assertEquals(ReportQueryExecutor::class, $decoratorDef->getClass());
        self::assertEquals(
            [
                new Reference('oro_report.datagrid_orm_query_executor.inner'),
                new Reference('doctrine'),
                $reportConnectionName,
                $reportDatagridPrefixes
            ],
            $decoratorDef->getArguments()
        );
        self::assertEquals(['oro_datagrid.orm.query_executor', null, 0], $decoratorDef->getDecoratedService());
        self::assertFalse($decoratorDef->isPublic());
    }

    public function testProcessWithReportDbalConnectionParameterButDbalConnectionWasNotConfigured()
    {
        $this->expectException(\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "reports" DBAL connection specified for "oro_report.connection" is not configured.'
        );

        $container = new ContainerBuilder();
        $container->register('oro_datagrid.orm.query_executor');
        $container->setParameter(DbalConnectionCompilerPass::CONNECTION_PARAM_NAME, 'reports');
        $container->setParameter(DbalConnectionCompilerPass::DATAGRID_PREFIXES_PARAM_NAME, []);

        $compiler = new DbalConnectionCompilerPass();
        $compiler->process($container);
    }
}
