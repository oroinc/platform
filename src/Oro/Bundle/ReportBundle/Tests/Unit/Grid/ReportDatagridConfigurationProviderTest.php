<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Grid;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\DatagridGuesserMock;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Grid\DatagridDateGroupingBuilder;
use Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationBuilder;
use Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationProvider;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class ReportDatagridConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ReportDatagridConfigurationProvider
     */
    protected $target;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $functionProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $virtualFieldProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrine;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var DatagridDateGroupingBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dateGroupingBuilder;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject | Cache
     */
    protected $reportCacheManager;

    /**
     * @var ReportDatagridConfigurationBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $this->functionProvider = $this->createMock(FunctionProviderInterface::class);
        $this->reportCacheManager = $this->createMock(Cache::class);
        $this->virtualFieldProvider = $this->createMock(VirtualFieldProviderInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->builder = new ReportDatagridConfigurationBuilder(
            $this->functionProvider,
            $this->virtualFieldProvider,
            $this->doctrine,
            new DatagridGuesserMock(),
            $entityNameResolver
        );

        $this->dateGroupingBuilder = $this->createMock(DatagridDateGroupingBuilder::class);

        $this->builder->setDateGroupingBuilder($this->dateGroupingBuilder);
        $this->builder->setConfigManager($this->configManager);

        $this->target = new ReportDatagridConfigurationProvider(
            $this->builder,
            $this->doctrine,
            $this->reportCacheManager,
            'someKey'
        );
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->target->isApplicable(Report::GRID_PREFIX . '1'));
        $this->assertFalse($this->target->isApplicable('oro_not_report_table_1'));
        $this->assertFalse($this->target->isApplicable('1_oro_report_table_1'));
    }

    public function testIsReportValid()
    {
        $this->reportCacheManager->expects(self::once())->method('fetch')->willReturn(false);
        $exception = new InvalidConfigurationException();
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willThrowException($exception);
        $this->assertFalse($this->target->isReportValid(''));
    }

    public function testGetConfigurationDoesNotAddActionIfNoRouteConfigured()
    {
        $gridName = Report::GRID_PREFIX . '1';
        $entity = Address::class;
        $this->prepareMetadata();
        $report = $this->getReportEntity($entity, ['columns' => ['column' => ['name' => 'street']]]);
        $this->prepareRepository($report);
        $this->reportCacheManager->expects(self::once())->method('fetch')->willReturn(false);

        $configuration = $this->target->getConfiguration($gridName);
        $this->assertEmpty($configuration->offsetGetByPath('[actions]'));
    }

    public function testGetConfigurationDoesNotAddActionIfDefinitionHaveGroupingNotByIdentifier()
    {
        $gridName = Report::GRID_PREFIX . '1';
        $entity = Address::class;

        $definition = [
            'columns'          => ['column' => ['name' => 'street']],
            'grouping_columns' => [
                ['name' => 'street']
            ]
        ];

        $this->reportCacheManager->expects(self::once())->method('fetch')->willReturn(false);

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

    public function testGetConfigurationDoesNotAddActionIfDefinitionHaveAggregationFunction()
    {
        $gridName = Report::GRID_PREFIX . '1';
        $entity = Address::class;

        $definition = [
            'columns' => [
                'column' => [
                    'name' => 'street',
                    'func' => [
                        'name'       => 'Sum',
                        'group_type' => 'aggregates',
                        'group_name' => 'number'
                    ]
                ]
            ]
        ];

        $this->reportCacheManager->expects(self::once())->method('fetch')->willReturn(false);

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

        $this->reportCacheManager->expects(self::once())->method('fetch')->willReturn(false);

        $metadata = $this->prepareMetadata();
        $metadata->expects($this->once())
            ->method('getIdentifier')
            ->willReturn([$expectedIdName]);

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
            if (strpos($selectPart, ".{$expectedIdName}") !== -1) {
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

        $report = $this->getReportEntity($entity, $definition);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->willReturn($entityMetadata);

        $expectedConfiguration = $this->buildConfiguration($gridName, $report);
        $this->reportCacheManager->expects(self::once())
            ->method('fetch')->willReturn($expectedConfiguration);

        $configuration = $this->target->getConfiguration($gridName);

        self::assertEquals($expectedConfiguration, $configuration);
    }

    /**
     * @param string $gridName
     * @param Report $report
     *
     * @return DatagridConfiguration
     */
    protected function buildConfiguration($gridName, Report $report)
    {
        $this->builder->setGridName($gridName);
        $this->builder->setSource($report);

        return $this->builder->getConfiguration();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function prepareMetadata()
    {
        $manager = $this->createMock(ObjectManager::class);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $metadata = $this->createMock(ClassMetadata::class);
        $manager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        return $metadata;
    }

    /**
     * Returns created report mock
     *
     * @param string $className
     * @param array  $definition
     *
     * @return Report|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getReportEntity($className, array $definition)
    {
        $report = $this->createMock(Report::class);
        $definition = json_encode($definition);

        //only stub because of calls time depend on DatagridConfigurationBuilder realisation
        $report->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);
        $report->expects($this->exactly(2))
            ->method('getEntity')
            ->willReturn($className);

        return $report;
    }

    /**
     * Initialises repository to return expected report entity
     *
     * @param Report $report
     */
    protected function prepareRepository(Report $report)
    {
        $repository = $this->createMock(ObjectRepository::class);
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
    protected function getEntityMetadata($viewRoute)
    {
        $entityMetadata = $this->createMock(EntityMetadata::class);
        $entityMetadata->routeView = $viewRoute;

        return $entityMetadata;
    }
}
