<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Grid;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;
use Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\DatagridGuesserMock;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Grid\DatagridDateGroupingBuilder;
use Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationBuilder;
use Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationProvider;

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
     * @var \PHPUnit\Framework\MockObject\MockObject|SystemAwareResolver
     */
    protected $resolver;

    /**
     * @var ReportDatagridConfigurationBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $this->functionProvider = $this->createMock(
            'Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface'
        );

        $this->reportCacheManager = $this->createMock('Doctrine\Common\Cache\Cache');

        $this->virtualFieldProvider = $this->createMock(
            'Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface'
        );

        $this->doctrine = $this->createMock('Symfony\Bridge\Doctrine\ManagerRegistry');

        $this->configManager = $this->createMock('Oro\Bundle\EntityConfigBundle\Config\ConfigManager');

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

        $this->resolver = $this->createMock(SystemAwareResolver::class);

        $this->target = new ReportDatagridConfigurationProvider(
            $this->builder,
            $this->doctrine,
            $this->reportCacheManager,
            $this->resolver,
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
        $this->doctrine->expects($this->once())->method('getRepository')
            ->will($this->throwException($exception));
        $this->assertFalse($this->target->isReportValid(''));
    }

    public function testGetConfigurationDoesNotAddActionIfNoRouteConfigured()
    {
        $gridName = Report::GRID_PREFIX . '1';
        $entity   = 'Oro\Bundle\AddressBundle\Entity\Address';
        $this->prepareMetadata();
        $report = $this->getReportEntity($entity, ['columns' => ['column' => ['name' => 'street']]]);
        $this->prepareRepository($report);
        $this->reportCacheManager->expects(self::once())->method('fetch')->willReturn(false);

        $this->resolver->expects($this->once())
            ->method('resolve')
            ->with($gridName, $this->isInstanceOf(DatagridConfiguration::class))
            ->willReturnArgument(1);

        $configuration = $this->target->getConfiguration($gridName);
        $this->assertEmpty($configuration->offsetGetByPath('[actions]'));
    }

    public function testGetConfigurationDoesNotAddActionIfDefinitionHaveGroupingNotByIdentifier()
    {
        $gridName = Report::GRID_PREFIX . '1';
        $entity   = 'Oro\Bundle\AddressBundle\Entity\Address';

        $definition = [
            'columns' => ['column' => ['name'=>'street']],
            'grouping_columns' => [
                ['name'=>'street']
            ]
        ];

        $this->reportCacheManager->expects(self::once())->method('fetch')->willReturn(false);

        $metadata = $this->prepareMetadata();

        //only stub
        $metadata->expects($this->any())
            ->method('getIdentifier')
            ->will($this->returnValue(['id']));

        $report = $this->getReportEntity($entity, $definition);
        $this->prepareRepository($report);

        $expectedViewRoute = 'oro_sample_view';
        $entityMetadata    = $this->getEntityMetadata($expectedViewRoute);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->will($this->returnValue($entityMetadata));

        $this->resolver->expects($this->once())
            ->method('resolve')
            ->with($gridName, $this->isInstanceOf(DatagridConfiguration::class))
            ->willReturnArgument(1);

        $configuration = $this->target->getConfiguration($gridName);
        $this->assertEmpty($configuration->offsetGetByPath('[actions]'));
    }

    public function testGetConfigurationDoesNotAddActionIfDefinitionHaveAggregationFunction()
    {
        $gridName = Report::GRID_PREFIX . '1';
        $entity   = 'Oro\Bundle\AddressBundle\Entity\Address';

        $definition = [
            'columns' => [
                'column' => [
                    'name' => 'street',
                    "func" => [
                        "name"       => "Sum",
                        "group_type" => "aggregates",
                        "group_name" => "number",
                    ]
                ]
            ]
        ];

        $this->reportCacheManager->expects(self::once())->method('fetch')->willReturn(false);

        $metadata = $this->prepareMetadata();

        //only stub
        $metadata->expects($this->any())
            ->method('getIdentifier')
            ->will($this->returnValue(['id']));

        $report = $this->getReportEntity($entity, $definition);
        $this->prepareRepository($report);

        $expectedViewRoute = 'oro_sample_view';
        $entityMetadata    = $this->getEntityMetadata($expectedViewRoute);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->will($this->returnValue($entityMetadata));

        $this->resolver->expects($this->once())
            ->method('resolve')
            ->with($gridName, $this->isInstanceOf(DatagridConfiguration::class))
            ->willReturnArgument(1);

        $configuration = $this->target->getConfiguration($gridName);
        $this->assertEmpty($configuration->offsetGetByPath('[actions]'));
    }

    public function testGetConfigurationAddAction()
    {
        $gridName = Report::GRID_PREFIX . '1';

        $expectedIdName = 'test_id';

        $entity = 'Oro\Bundle\AddressBundle\Entity\Address';

        $expectedViewRoute = 'oro_sample_view';
        $entityMetadata    = $this->getEntityMetadata($expectedViewRoute);

        $this->reportCacheManager->expects(self::once())->method('fetch')->willReturn(false);

        $metadata = $this->prepareMetadata();
        $metadata->expects($this->once())
            ->method('getIdentifier')
            ->will($this->returnValue([$expectedIdName]));

        $definition = [
            'columns' => [
                [
                    'name' => $expectedIdName
                ],
                [
                    'name' => 'street',
                    "func" => [
                        "name"       => "Sum",
                        "group_type" => "aggregates",
                        "group_name" => "number",
                    ]
                ]
            ],
            'grouping_columns' => [
                ['name'=>$expectedIdName]
            ]
        ];

        $report = $this->getReportEntity($entity, $definition);
        $this->prepareRepository($report);
        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->will($this->returnValue($entityMetadata));

        $this->resolver->expects($this->once())
            ->method('resolve')
            ->with($gridName, $this->isInstanceOf(DatagridConfiguration::class))
            ->willReturnArgument(1);

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

        $selectParts           = $configuration->offsetGetByPath('[source][query][select]');
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

        $entity = 'Oro\Bundle\AddressBundle\Entity\Address';

        $expectedViewRoute = 'oro_sample_view';
        $entityMetadata    = $this->getEntityMetadata($expectedViewRoute);

        $metadata = $this->prepareMetadata();
        $metadata->expects($this->once())
            ->method('getIdentifier')
            ->will($this->returnValue([$expectedIdName]));

        $definition = [
            'columns' => [
                [
                    'name' => $expectedIdName
                ],
                [
                    'name' => 'street',
                    "func" => [
                        "name"       => "Sum",
                        "group_type" => "aggregates",
                        "group_name" => "number",
                    ]
                ]
            ],
            'grouping_columns' => [
                ['name'=>$expectedIdName]
            ]
        ];

        $report = $this->getReportEntity($entity, $definition);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->will($this->returnValue($entityMetadata));

        $this->resolver->expects($this->once())
            ->method('resolve')
            ->with($gridName, $this->isInstanceOf(DatagridConfiguration::class))
            ->willReturnArgument(1);

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
        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($manager));

        $metadata = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));

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
        $report     = $this->createMock('Oro\Bundle\ReportBundle\Entity\Report');
        $definition = json_encode($definition);

        //only stub because of calls time depend on DatagridConfigurationBuilder realisation
        $report->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue($definition));
        $report->expects($this->exactly(2))
            ->method('getEntity')
            ->will($this->returnValue($className));

        return $report;
    }

    /**
     * Initialises repository to return expected report entity
     *
     * @param Report $report
     */
    protected function prepareRepository(Report $report)
    {
        $repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('find')
            ->will($this->returnValue($report));
        $this->doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));
    }

    /**
     * @param string $viewRoute
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getEntityMetadata($viewRoute)
    {
        $entityMetadata = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $entityMetadata->routeView = $viewRoute;

        return $entityMetadata;
    }
}
