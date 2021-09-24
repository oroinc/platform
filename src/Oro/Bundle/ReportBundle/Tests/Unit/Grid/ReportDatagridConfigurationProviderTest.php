<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Grid;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Grid\DatagridDateGroupingBuilder;
use Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationBuilder;
use Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationProvider;

class ReportDatagridConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ReportDatagridConfigurationProvider */
    private $target;

    /** @var FunctionProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $functionProvider;

    /** @var VirtualFieldProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $virtualFieldProvider;

    /** @var VirtualRelationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $virtualRelationProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var DatagridDateGroupingBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $dateGroupingBuilder;

    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var ReportDatagridConfigurationBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->functionProvider = $this->createMock(FunctionProviderInterface::class);
        $this->virtualFieldProvider = $this->createMock(VirtualFieldProviderInterface::class);
        $this->virtualRelationProvider = $this->createMock(VirtualRelationProviderInterface::class);
        $this->cache = $this->createMock(Cache::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->builder = new ReportDatagridConfigurationBuilder(
            $this->functionProvider,
            $this->virtualFieldProvider,
            $this->virtualRelationProvider,
            new DoctrineHelper($this->doctrine),
            new DatagridGuesser([]),
            $entityNameResolver
        );

        $this->dateGroupingBuilder = $this->createMock(DatagridDateGroupingBuilder::class);

        $this->builder->setDateGroupingBuilder($this->dateGroupingBuilder);
        $this->builder->setConfigManager($this->configManager);

        $this->target = new ReportDatagridConfigurationProvider(
            $this->builder,
            $this->doctrine,
            $this->cache,
            'someKey'
        );
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->target->isApplicable(Report::GRID_PREFIX . '1'));
        $this->assertFalse($this->target->isApplicable('oro_not_report_table_1'));
        $this->assertFalse($this->target->isApplicable('1_oro_report_table_1'));
    }

    public function testIsReportValidForInvalidConfiguration()
    {
        $this->cache->expects(self::once())
            ->method('fetch')
            ->willReturn(false);
        $repository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(Report::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('find')
            ->with($this->identicalTo(1))
            ->willThrowException(new InvalidConfigurationException());

        $this->assertFalse($this->target->isReportValid(Report::GRID_PREFIX . '1'));
    }

    public function testGetConfigurationDoesNotAddActionIfNoRouteConfigured()
    {
        $gridName = Report::GRID_PREFIX . '1';
        $entity = Address::class;

        $this->virtualFieldProvider->expects($this->any())
            ->method('isVirtualField')
            ->with($entity, $this->anything())
            ->willReturn(false);

        $this->prepareMetadata();

        $report = $this->getReportEntity($entity, ['columns' => [['name' => 'street']]]);
        $this->prepareRepository($report);
        $this->cache->expects(self::once())
            ->method('fetch')
            ->willReturn(false);

        $configuration = $this->target->getConfiguration($gridName);
        $this->assertEmpty($configuration->offsetGetByPath('[actions]'));
    }

    public function testGetConfigurationDoesNotAddActionIfDefinitionHaveGroupingNotByIdentifier()
    {
        $gridName = Report::GRID_PREFIX . '1';
        $entity = Address::class;

        $definition = [
            'columns'          => [
                ['name' => 'street']
            ],
            'grouping_columns' => [
                ['name' => 'street']
            ]
        ];

        $this->cache->expects(self::once())
            ->method('fetch')
            ->willReturn(false);

        $this->virtualFieldProvider->expects($this->any())
            ->method('isVirtualField')
            ->with($entity, $this->anything())
            ->willReturn(false);

        $metadata = $this->prepareMetadata();

        //only stub
        $metadata->expects($this->any())
            ->method('getIdentifier')
            ->willReturn(['id']);
        $metadata->expects($this->any())
            ->method('getAssociationNames')
            ->willReturn([]);

        $report = $this->getReportEntity($entity, $definition);
        $this->prepareRepository($report);

        $expectedViewRoute = 'oro_sample_view';
        $entityMetadata = $this->getEntityMetadata($expectedViewRoute);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->willReturn($entityMetadata);

        $configuration = $this->target->getConfiguration($gridName);
        $this->assertEmpty($configuration->offsetGetByPath('[actions]'));
    }

    public function testGetConfigurationDoesNotAddActionIfDefinitionHaveAggregationFunction()
    {
        $gridName = Report::GRID_PREFIX . '1';
        $entity = Address::class;

        $definition = [
            'columns' => [
                [
                    'name' => 'street',
                    'func' => [
                        'name'       => 'Sum',
                        'group_type' => 'aggregates',
                        'group_name' => 'number'
                    ]
                ]
            ]
        ];

        $this->functionProvider->expects($this->once())
            ->method('getFunction')
            ->willReturn(['name' => 'Sum', 'expr' => 'SUM($column)']);

        $this->virtualFieldProvider->expects($this->any())
            ->method('isVirtualField')
            ->with($entity, $this->anything())
            ->willReturn(false);

        $this->cache->expects(self::once())
            ->method('fetch')
            ->willReturn(false);

        $metadata = $this->prepareMetadata();

        //only stub
        $metadata->expects($this->any())
            ->method('getIdentifier')
            ->willReturn(['id']);

        $report = $this->getReportEntity($entity, $definition);
        $this->prepareRepository($report);

        $expectedViewRoute = 'oro_sample_view';
        $entityMetadata = $this->getEntityMetadata($expectedViewRoute);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->willReturn($entityMetadata);

        $configuration = $this->target->getConfiguration($gridName);
        $this->assertEmpty($configuration->offsetGetByPath('[actions]'));
    }

    public function testGetConfigurationAddAction()
    {
        $gridName = Report::GRID_PREFIX . '1';

        $expectedIdName = 'test_id';

        $entity = Address::class;

        $expectedViewRoute = 'oro_sample_view';
        $entityMetadata = $this->getEntityMetadata($expectedViewRoute);

        $this->cache->expects(self::once())
            ->method('fetch')
            ->willReturn(false);

        $metadata = $this->prepareMetadata();
        $metadata->expects($this->once())
            ->method('getIdentifier')
            ->willReturn([$expectedIdName]);
        $metadata->expects($this->any())
            ->method('getAssociationNames')
            ->willReturn([]);

        $definition = [
            'columns'          => [
                [
                    'name' => $expectedIdName
                ],
                [
                    'name' => 'street',
                    'func' => [
                        'name'       => 'Sum',
                        'group_type' => 'aggregates',
                        'group_name' => 'number'
                    ]
                ]
            ],
            'grouping_columns' => [
                ['name' => $expectedIdName]
            ]
        ];

        $this->functionProvider->expects($this->once())
            ->method('getFunction')
            ->willReturn(['name' => 'Sum', 'expr' => 'SUM($column)']);

        $this->virtualFieldProvider->expects($this->any())
            ->method('isVirtualField')
            ->with($entity, $this->anything())
            ->willReturn(false);

        $report = $this->getReportEntity($entity, $definition);
        $this->prepareRepository($report);
        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->willReturn($entityMetadata);

        $configuration = $this->target->getConfiguration($gridName);

        $actualProperties = $configuration->offsetGetByPath('[properties]');
        $this->assertArrayHasKey('view_link', $actualProperties);

        $expectedProperties = [
            $expectedIdName => null,
            'view_link'     => [
                'type'   => 'url',
                'route'  => $expectedViewRoute,
                'params' => [$expectedIdName]
            ]
        ];
        $this->assertEquals($expectedProperties, $actualProperties);

        $selectParts = $configuration->offsetGetByPath('[source][query][select]');
        $selectIdentifierExist = false;
        foreach ($selectParts as $selectPart) {
            if (strpos($selectPart, '.' . $expectedIdName) !== -1) {
                $selectIdentifierExist = true;
            }
        }
        $this->assertTrue($selectIdentifierExist);

        $expectedActions = [
            'view' => [
                'type'         => 'navigate',
                'label'        => 'oro.report.datagrid.row.action.view',
                'acl_resource' => 'VIEW;entity:Oro\Bundle\AddressBundle\Entity\Address',
                'icon'         => 'eye',
                'link'         => 'view_link',
                'rowAction'    => true
            ]
        ];
        $this->assertEquals($expectedActions, $configuration->offsetGetByPath('[actions]'));
    }

    public function testGetDataFromCache()
    {
        $gridName = Report::GRID_PREFIX . '1';

        $expectedIdName = 'test_id';

        $entity = Address::class;

        $expectedViewRoute = 'oro_sample_view';
        $entityMetadata = $this->getEntityMetadata($expectedViewRoute);

        $metadata = $this->prepareMetadata();
        $metadata->expects($this->once())
            ->method('getIdentifier')
            ->willReturn([$expectedIdName]);
        $metadata->expects($this->any())
            ->method('getAssociationNames')
            ->willReturn([]);

        $definition = [
            'columns'          => [
                [
                    'name' => $expectedIdName
                ],
                [
                    'name' => 'street',
                    'func' => [
                        'name'       => 'Sum',
                        'group_type' => 'aggregates',
                        'group_name' => 'number'
                    ]
                ]
            ],
            'grouping_columns' => [
                ['name' => $expectedIdName]
            ]
        ];

        $this->functionProvider->expects($this->once())
            ->method('getFunction')
            ->willReturn(['name' => 'Sum', 'expr' => 'SUM($column)']);

        $this->virtualFieldProvider->expects($this->any())
            ->method('isVirtualField')
            ->with($entity, $this->anything())
            ->willReturn(false);

        $report = $this->getReportEntity($entity, $definition);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->willReturn($entityMetadata);

        $expectedConfiguration = $this->buildConfiguration($gridName, $report);
        $this->cache->expects(self::once())
            ->method('fetch')
            ->willReturn($expectedConfiguration);

        $configuration = $this->target->getConfiguration($gridName);

        self::assertEquals($expectedConfiguration, $configuration);
    }

    /**
     * @param string $gridName
     * @param Report $report
     *
     * @return DatagridConfiguration
     */
    private function buildConfiguration($gridName, Report $report)
    {
        $this->builder->setGridName($gridName);
        $this->builder->setSource($report);

        return $this->builder->getConfiguration();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function prepareMetadata()
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        return $metadata;
    }

    /**
     * @param string $entityClass
     * @param array  $definition
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

    /**
     * Initialises repository to return expected report entity
     */
    private function prepareRepository(Report $report)
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->willReturn($report);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
    }

    /**
     * @param string $viewRoute
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getEntityMetadata($viewRoute)
    {
        $entityMetadata = $this->createMock(EntityMetadata::class);
        $entityMetadata->routeView = $viewRoute;

        return $entityMetadata;
    }
}
