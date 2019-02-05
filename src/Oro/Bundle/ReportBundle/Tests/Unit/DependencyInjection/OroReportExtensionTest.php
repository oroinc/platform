<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ReportBundle\DependencyInjection\Compiler\DbalConnectionCompilerPass;
use Oro\Bundle\ReportBundle\DependencyInjection\OroReportExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroReportExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadEmptyConfig()
    {
        $configs = [];

        $container = new ContainerBuilder();
        $extension = new OroReportExtension();
        $extension->load($configs, $container);

        self::assertFalse($container->hasParameter(DbalConnectionCompilerPass::CONNECTION_PARAM_NAME));
        self::assertFalse($container->hasParameter(DbalConnectionCompilerPass::DATAGRID_PREFIXES_PARAM_NAME));

        self::assertEquals(
            [
                [
                    'settings' => [
                        'resolved'          => true,
                        'display_sql_query' => ['value' => false, 'scope' => 'app']
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_report')
        );
    }

    public function testLoadConfigWithReportDbalConnection()
    {
        $configs = [
            [
                'dbal' => [
                    'connection' => 'reports'
                ]
            ]
        ];

        $container = new ContainerBuilder();
        $extension = new OroReportExtension();
        $extension->load($configs, $container);

        self::assertEquals(
            $configs[0]['dbal']['connection'],
            $container->getParameter(DbalConnectionCompilerPass::CONNECTION_PARAM_NAME)
        );
        self::assertEquals(
            [],
            $container->getParameter(DbalConnectionCompilerPass::DATAGRID_PREFIXES_PARAM_NAME)
        );
    }

    public function testLoadConfigWithReportDbalConnectionAndDatagridPrefixes()
    {
        $configs = [
            [
                'dbal' => [
                    'connection'        => 'reports',
                    'datagrid_prefixes' => ['prefix1']
                ]
            ]
        ];

        $container = new ContainerBuilder();
        $extension = new OroReportExtension();
        $extension->load($configs, $container);

        self::assertEquals(
            $configs[0]['dbal']['connection'],
            $container->getParameter(DbalConnectionCompilerPass::CONNECTION_PARAM_NAME)
        );
        self::assertEquals(
            $configs[0]['dbal']['datagrid_prefixes'],
            $container->getParameter(DbalConnectionCompilerPass::DATAGRID_PREFIXES_PARAM_NAME)
        );
    }

    public function testLoadConfigWithReportDbalDatagridPrefixesButWithoutConnection()
    {
        $configs = [
            [
                'dbal' => [
                    'datagrid_prefixes' => ['prefix1']
                ]
            ]
        ];

        $container = new ContainerBuilder();
        $extension = new OroReportExtension();
        $extension->load($configs, $container);

        self::assertFalse($container->hasParameter(DbalConnectionCompilerPass::CONNECTION_PARAM_NAME));
        self::assertFalse($container->hasParameter(DbalConnectionCompilerPass::DATAGRID_PREFIXES_PARAM_NAME));
    }
}
