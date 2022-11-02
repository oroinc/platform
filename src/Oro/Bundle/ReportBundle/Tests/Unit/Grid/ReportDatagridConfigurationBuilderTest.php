<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Grid;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Grid\DatagridDateGroupingBuilder;
use Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationBuilder;

class ReportDatagridConfigurationBuilderTest extends \PHPUnit\Framework\TestCase
{
    private DatagridDateGroupingBuilder|\PHPUnit\Framework\MockObject\MockObject $dateGroupingBuilder;

    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine;

    private ReportDatagridConfigurationBuilder $builder;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $functionProvider = $this->createMock(FunctionProviderInterface::class);
        $virtualFieldProvider = $this->createMock(VirtualFieldProviderInterface::class);
        $virtualRelationProvider = $this->createMock(VirtualRelationProviderInterface::class);
        $this->dateGroupingBuilder = $this->createMock(DatagridDateGroupingBuilder::class);

        $virtualFieldProvider->expects(self::any())
            ->method('isVirtualField')
            ->willReturn(false);

        $this->builder = new ReportDatagridConfigurationBuilder(
            $functionProvider,
            $virtualFieldProvider,
            $virtualRelationProvider,
            new DoctrineHelper($this->doctrine),
            new DatagridGuesser([]),
            $this->createMock(EntityNameResolver::class)
        );
        $this->builder->setDateGroupingBuilder($this->dateGroupingBuilder);
        $this->builder->setConfigManager($this->configManager);
    }

    public function testGetConfigurationWhenNoGridName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Grid name not configured');

        $this->builder->getConfiguration();
    }

    public function testGetConfigurationWhenNoSource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source is missing');

        $this->builder->setGridName('sample-grid');
        $this->builder->getConfiguration();
    }

    public function testGetConfigurationWhenNoMetadata(): void
    {
        $this->mockClassMetadata([]);

        $this->configManager->expects(self::any())
            ->method('getEntityMetadata')
            ->with(\stdClass::class)
            ->willReturn(null);

        $report = $this->getReportEntity(\stdClass::class, $this->getSimpleDefinition());
        $this->builder->setGridName($gridName = 'sample-grid');
        $this->builder->setSource($report);

        $this->dateGroupingBuilder->expects(self::once())
            ->method('applyDateGroupingFilterIfRequired')
            ->with(self::isInstanceOf(DatagridConfiguration::class), $report);

        self::assertEquals($this->getSimpleConfiguration($gridName), $this->builder->getConfiguration()->toArray());
    }

    private function mockClassMetadata(array $identifiers)
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($em);
        $em->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $metadata->expects(self::any())
            ->method('getIdentifier')
            ->willReturn($identifiers);
        $metadata->expects(self::any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn(reset($identifiers));
    }

    private function getSimpleDefinition(): array
    {
        return ['columns' => [['name' => 'sampleColumn']]];
    }

    private function getSimpleConfiguration(string $gridName): array
    {
        return [
            'name' => $gridName,
            'columns' => [
                'c1' => [
                    'label' => 'sampleColumn',
                    'translatable' => false,
                ],
            ],
            'sorters' => ['columns' => ['c1' => ['data_name' => 'c1']]],
            'filters' => ['columns' => ['c1' => ['data_name' => 'c1', 'translatable' => false]]],
            'fields_acl' => ['columns' => ['c1' => ['data_name' => 't1.sampleColumn']]],
            'source' => [
                'query' => [
                    'select' => ['t1.sampleColumn as c1'],
                    'from' => [['table' => 'stdClass', 'alias' => 't1']],
                ],
                'query_config' => [
                    'table_aliases' => ['' => 't1'],
                    'column_aliases' => ['sampleColumn' => 'c1'],
                ],
                'type' => 'orm',
                'acl_resource' => 'oro_report_view',
            ],
            'options' => ['export' => true, 'entity_pagination' => true],
        ];
    }

    public function testGetConfigurationWhenNoRouteView(): void
    {
        $this->mockClassMetadata([]);

        $this->mockEntityMetadata('');

        $gridName = 'sample-grid';
        $this->builder->setGridName($gridName);
        $this->builder->setSource($this->getReportEntity(\stdClass::class, $this->getSimpleDefinition()));

        self::assertEquals($this->getSimpleConfiguration($gridName), $this->builder->getConfiguration()->toArray());
    }

    private function mockEntityMetadata(string $viewRoute): void
    {
        $entityMetadata = new EntityMetadata(\stdClass::class);
        $entityMetadata->routeView = $viewRoute;

        $this->configManager->expects(self::any())
            ->method('getEntityMetadata')
            ->with(\stdClass::class)
            ->willReturn($entityMetadata);
    }

    /**
     * @dataProvider invalidIdentifiersDataProvider
     */
    public function testGetConfigurationWhenInvalidIdentifier(array $identifiers): void
    {
        $this->mockClassMetadata($identifiers);

        $this->mockEntityMetadata('sample_route');

        $gridName = 'sample-grid';
        $this->builder->setGridName($gridName);
        $this->builder->setSource($this->getReportEntity(\stdClass::class, $this->getSimpleDefinition()));

        self::assertEquals($this->getSimpleConfiguration($gridName), $this->builder->getConfiguration()->toArray());
    }

    public function invalidIdentifiersDataProvider(): array
    {
        return [
            'empty' => ['identifiers' => []],
            'multiple' => ['identifiers' => ['sampleId1', 'sampleId2']],
        ];
    }

    public function testGetConfiguration(): void
    {
        $this->mockClassMetadata(['sampleId1']);

        $this->mockEntityMetadata('sample_route');

        $gridName = 'sample-grid';
        $this->builder->setGridName($gridName);
        $this->builder->setSource($this->getReportEntity(\stdClass::class, $this->getSimpleDefinition()));

        self::assertEquals($this->getExpectedConfiguration($gridName), $this->builder->getConfiguration()->toArray());
    }

    private function getExpectedConfiguration(string $gridName): array
    {
        return [
            'name' => $gridName,
            'columns' => [
                'c1' => [
                    'label' => 'sampleColumn',
                    'translatable' => false,
                ],
            ],
            'sorters' => ['columns' => ['c1' => ['data_name' => 'c1']]],
            'filters' => ['columns' => ['c1' => ['data_name' => 'c1', 'translatable' => false]]],
            'fields_acl' => ['columns' => ['c1' => ['data_name' => 't1.sampleColumn']]],
            'source' => [
                'query' => [
                    'select' => [
                        0 => 't1.sampleColumn as c1',
                        1 => 't1.sampleId1',
                    ],
                    'from' => [
                        0 => [
                            'table' => 'stdClass',
                            'alias' => 't1',
                        ],
                    ],
                ],
                'query_config' => [
                    'table_aliases' => [
                        '' => 't1',
                    ],
                    'column_aliases' => [
                        'sampleColumn' => 'c1',
                    ],
                ],
                'type' => 'orm',
                'hints' => [
                    'HINT_TRANSLATABLE',
                ],
                'acl_resource' => 'oro_report_view',
            ],
            'properties' => [
                'sampleId1' => null,
                'view_link' => [
                    'type' => 'url',
                    'route' => 'sample_route',
                    'params' => [
                        0 => 'sampleId1',
                    ],
                ],
            ],
            'actions' => [
                'view' => [
                    'type' => 'navigate',
                    'label' => 'oro.report.datagrid.row.action.view',
                    'acl_resource' => 'VIEW;entity:stdClass',
                    'icon' => 'eye',
                    'link' => 'view_link',
                    'rowAction' => true,
                ],
            ],
            'options' => ['export' => true, 'entity_pagination' => true],
        ];
    }

    private function getReportEntity(string $entityClass, array $definition): Report
    {
        $report = $this->createMock(Report::class);
        $report->expects(self::any())
            ->method('getDefinition')
            ->willReturn(QueryDefinitionUtil::encodeDefinition($definition));
        $report->expects(self::any())
            ->method('getEntity')
            ->willReturn($entityClass);

        return $report;
    }
}
