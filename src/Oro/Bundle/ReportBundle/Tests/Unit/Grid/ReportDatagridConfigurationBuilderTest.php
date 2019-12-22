<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Grid;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\DatagridGuesserMock;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\SqlWalker;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Grid\DatagridDateGroupingBuilder;
use Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationBuilder;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class ReportDatagridConfigurationBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridDateGroupingBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $dateGroupingBuilder;

    /** @var FunctionProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $functionProvider;

    /** @var VirtualFieldProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $virtualFieldProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    private $doctrine;

    /** @var ReportDatagridConfigurationBuilder */
    private $builder;

    protected function setUp()
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->functionProvider = $this->createMock(FunctionProviderInterface::class);
        $this->virtualFieldProvider = $this->createMock(VirtualFieldProviderInterface::class);
        $this->dateGroupingBuilder = $this->createMock(DatagridDateGroupingBuilder::class);

        $entityNameResolver = $this->createMock(EntityNameResolver::class);

        $this->builder = new ReportDatagridConfigurationBuilder(
            $this->functionProvider,
            $this->virtualFieldProvider,
            $this->doctrine,
            new DatagridGuesserMock(),
            $entityNameResolver
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

    /**
     * @param array $identifiers
     *
     * @return ClassMetadata
     */
    private function mockClassMetadata(array $identifiers): ClassMetadata
    {
        $this->doctrine
            ->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager = $this->createMock(ObjectManager::class));

        $manager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata = $this->createMock(ClassMetadata::class));

        $metadata->expects($this->any())
            ->method('getIdentifier')
            ->willReturn($identifiers);

        return $metadata;
    }

    /**
     * @return array
     */
    private function getSimpleDefinition(): array
    {
        return ['columns' => [['name' => 'sampleColumn']]];
    }

    /**
     * @param string $gridName
     *
     * @return array
     */
    private function getSimpleConfiguration(string $gridName): array
    {
        return [
            'name' => $gridName,
            'columns' => [
                'c1' => [
                    'frontend_type' => null,
                    'label' => 'sampleColumn',
                    'translatable' => false,
                ],
            ],
            'sorters' => ['columns' => ['c1' => ['data_name' => 'c1']]],
            'filters' => ['columns' => ['c1' => ['type' => null, 'data_name' => 'c1', 'translatable' => false]]],
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
                'hints' => [
                    [
                        'name' => 'doctrine.customOutputWalker',
                        'value' => SqlWalker::class,
                    ],
                ],
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

    /**
     * @param string $viewRoute
     *
     * @return EntityMetadata
     */
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
     *
     * @param array $identifiers
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

    /**
     * @return array
     */
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

    /**
     * @param string $gridName
     *
     * @return array
     */
    private function getExpectedConfiguration(string $gridName): array
    {
        return [
            'name' => $gridName,
            'columns' => [
                'c1' => [
                    'frontend_type' => null,
                    'label' => 'sampleColumn',
                    'translatable' => false,
                ],
            ],
            'sorters' => ['columns' => ['c1' => ['data_name' => 'c1']]],
            'filters' => ['columns' => ['c1' => ['type' => null, 'data_name' => 'c1', 'translatable' => false]]],
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
                    0 => [
                        'name' => 'doctrine.customOutputWalker',
                        'value' => SqlWalker::class,
                    ],
                    1 => 'HINT_TRANSLATABLE',
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
     * @param string $className
     * @param array $definition
     *
     * @return Report|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getReportEntity($className, array $definition)
    {
        $report = $this->createMock(Report::class);
        $definition = json_encode($definition);

        $report
            ->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        $report
            ->expects($this->any())
            ->method('getEntity')
            ->willReturn($className);

        return $report;
    }
}
