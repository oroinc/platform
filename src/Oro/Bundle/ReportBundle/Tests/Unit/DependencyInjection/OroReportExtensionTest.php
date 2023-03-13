<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ReportBundle\DependencyInjection\Compiler\DbalConnectionCompilerPass;
use Oro\Bundle\ReportBundle\DependencyInjection\OroReportExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroReportExtensionTest extends \PHPUnit\Framework\TestCase
{
    private ContainerBuilder $container;
    private OroReportExtension $extension;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroReportExtension();
    }

    public function testLoad(): void
    {
        $this->extension->load([], $this->container);

        self::assertFalse($this->container->hasParameter(DbalConnectionCompilerPass::CONNECTION_PARAM_NAME));
        self::assertFalse($this->container->hasParameter(DbalConnectionCompilerPass::DATAGRID_PREFIXES_PARAM_NAME));

        self::assertEquals(
            [
                [
                    'settings' => [
                        'resolved'          => true,
                        'display_sql_query' => ['value' => false, 'scope' => 'app']
                    ]
                ]
            ],
            $this->container->getExtensionConfig('oro_report')
        );
    }

    public function testLoadConfigWithReportDbalConnection(): void
    {
        $configs = [
            [
                'dbal' => [
                    'connection' => 'reports'
                ]
            ]
        ];

        $this->extension->load($configs, $this->container);

        self::assertEquals(
            $configs[0]['dbal']['connection'],
            $this->container->getParameter(DbalConnectionCompilerPass::CONNECTION_PARAM_NAME)
        );
        self::assertEquals(
            [],
            $this->container->getParameter(DbalConnectionCompilerPass::DATAGRID_PREFIXES_PARAM_NAME)
        );
    }

    public function testLoadConfigWithReportDbalConnectionAndDatagridPrefixes(): void
    {
        $configs = [
            [
                'dbal' => [
                    'connection'        => 'reports',
                    'datagrid_prefixes' => ['prefix1']
                ]
            ]
        ];

        $this->extension->load($configs, $this->container);

        self::assertEquals(
            $configs[0]['dbal']['connection'],
            $this->container->getParameter(DbalConnectionCompilerPass::CONNECTION_PARAM_NAME)
        );
        self::assertEquals(
            $configs[0]['dbal']['datagrid_prefixes'],
            $this->container->getParameter(DbalConnectionCompilerPass::DATAGRID_PREFIXES_PARAM_NAME)
        );
    }

    public function testLoadConfigWithReportDbalDatagridPrefixesButWithoutConnection(): void
    {
        $configs = [
            [
                'dbal' => [
                    'datagrid_prefixes' => ['prefix1']
                ]
            ]
        ];

        $this->extension->load($configs, $this->container);

        self::assertFalse($this->container->hasParameter(DbalConnectionCompilerPass::CONNECTION_PARAM_NAME));
        self::assertFalse($this->container->hasParameter(DbalConnectionCompilerPass::DATAGRID_PREFIXES_PARAM_NAME));
    }
}
