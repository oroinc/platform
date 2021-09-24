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
    /** @var DatagridDateGroupingBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $dateGroupingBuilder;

    /** @var FunctionProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $functionProvider;

    /** @var VirtualFieldProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $virtualFieldProvider;

    /** @var VirtualRelationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $virtualRelationProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ReportDatagridConfigurationBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->functionProvider = $this->createMock(FunctionProviderInterface::class);
        $this->virtualFieldProvider = $this->createMock(VirtualFieldProviderInterface::class);
        $this->virtualRelationProvider = $this->createMock(VirtualRelationProviderInterface::class);
        $this->dateGroupingBuilder = $this->createMock(DatagridDateGroupingBuilder::class);

        $entityNameResolver = $this->createMock(EntityNameResolver::class);

        $this->builder = new ReportDatagridConfigurationBuilder(
            $this->functionProvider,
            $this->virtualFieldProvider,
            $this->virtualRelationProvider,
            new DoctrineHelper($this->doctrine),
            new DatagridGuesser([]),
            $entityNameResolver
        );

        $this->builder->setDateGroupingBuilder($this->dateGroupingBuilder);
        $this->builder->setConfigManager($this->configManager);

        $this->virtualFieldProvider->expects($this->any())
            ->method('isVirtualField')
            ->willReturn(false);
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

        $this->configManager
            ->method('getEntityMetadata')
            ->with($entity = \stdClass::class)
            ->willReturn(null);

        $this->builder->setGridName($gridName = 'sample-grid');
        $this->builder->setSource(
            $report = $this->getReportEntity(\stdClass::class, $this->getSimpleDefinition())
        );

        $this->dateGroupingBuilder
            ->expects($this->once())
            ->method('applyDateGroupingFilterIfRequired')
            ->with($this->isInstanceOf(DatagridConfiguration::class), $report);

        $this->assertEquals($this->getSimpleConfiguration($gridName), $this->builder->getConfiguration()->toArray());
    }

    private function mockClassMetadata(array $identifiers): ClassMetadata
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $metadata->expects($this->any())
            ->method('getIdentifier')
            ->willReturn($identifiers);
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn(reset($identifiers));

        return $metadata;
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

        $this->builder->setGridName($gridName = 'sample-grid');
        $this->builder->setSource(
            $report = $this->getReportEntity(\stdClass::class, $this->getSimpleDefinition())
        );

        $this->assertEquals($this->getSimpleConfiguration($gridName), $this->builder->getConfiguration()->toArray());
    }

    private function mockEntityMetadata(string $viewRoute): EntityMetadata
    {
        $this->configManager
            ->method('getEntityMetadata')
            ->with($entity = \stdClass::class)
            ->willReturn($entityMetadata = $this->createMock(EntityMetadata::class));

        $entityMetadata->routeView = $viewRoute;

        return $entityMetadata;
    }

    /**
     * @dataProvider invalidIdentifiersDataProvider
     */
    public function testGetConfigurationWhenInvalidIdentifier(array $identifiers): void
    {
        $this->mockClassMetadata($identifiers);

        $this->mockEntityMetadata('sample_route');

        $this->builder->setGridName($gridName = 'sample-grid');
        $this->builder->setSource(
            $report = $this->getReportEntity(\stdClass::class, $this->getSimpleDefinition())
        );

        $this->assertEquals($this->getSimpleConfiguration($gridName), $this->builder->getConfiguration()->toArray());
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

        $this->builder->setGridName($gridName = 'sample-grid');
        $this->builder->setSource(
            $report = $this->getReportEntity(\stdClass::class, $this->getSimpleDefinition())
        );

        $this->assertEquals($this->getExpectedConfiguration($gridName), $this->builder->getConfiguration()->toArray());
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

    /**
     * @dataProvider groupByDataProvider
     */
    public function testGetConfigurationExportOption(array $groupBy, int $associationType, bool $expected): void
    {
        $metadata = $this->mockClassMetadata(['sampleId1']);
        $this->mockEntityMetadata('sample_route');

        $metadata->expects($this->any())
            ->method('getAssociationNames')
            ->willReturn(['sampleColumn']);
        $metadata->expects($this->any())
            ->method('getAssociationMapping')
            ->with('sampleColumn')
            ->willReturn(['type' => $associationType]);

        $gridName = 'sample-grid';
        $this->builder->setGridName($gridName);

        $definition = [
            'columns' => [['name' => 'sampleColumn'], ['name' => 'sampleId1']],
            'grouping_columns' => $groupBy
        ];
        $this->builder->setSource($this->getReportEntity(\stdClass::class, $definition));
        $config = $this->builder->getConfiguration()->toArray();
        $this->assertEquals($expected, $config['options']['export']);
    }

    public function groupByDataProvider()
    {
        yield [
            [['name' => 'sampleColumn']],
            ClassMetadata::MANY_TO_MANY,
            true
        ];

        yield [
            [['name' => 'sampleColumn']],
            ClassMetadata::ONE_TO_ONE,
            true
        ];

        yield [
            [['name' => 'sampleColumn']],
            ClassMetadata::ONE_TO_MANY,
            true
        ];

        yield [
            [['name' => 'sampleColumn']],
            ClassMetadata::MANY_TO_ONE,
            false
        ];

        yield [
            [['name' => 'sampleColumn'], ['name' => 'sampleId1']],
            ClassMetadata::MANY_TO_ONE,
            true
        ];

        yield [
            [['name' => 'sampleColumn'], ['name' => 'sampleId1']],
            ClassMetadata::ONE_TO_MANY,
            true
        ];
    }

    /**
     * @param string $entityClass
     * @param array $definition
     *
     * @return Report|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getReportEntity(string $entityClass, array $definition): Report
    {
        $report = $this->createMock(Report::class);
        $report->expects($this->any())
            ->method('getDefinition')
            ->willReturn(QueryDefinitionUtil::encodeDefinition($definition));
        $report->expects($this->any())
            ->method('getEntity')
            ->willReturn($entityClass);

        return $report;
    }
}
