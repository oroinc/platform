<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Grid;

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
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ReportDatagridConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    private ReportDatagridConfigurationProvider $target;

    private FunctionProviderInterface|\PHPUnit\Framework\MockObject\MockObject $functionProvider;

    private VirtualFieldProviderInterface|\PHPUnit\Framework\MockObject\MockObject $virtualFieldProvider;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine;

    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    private CacheInterface|\PHPUnit\Framework\MockObject\MockObject $cache;

    private ReportDatagridConfigurationBuilder $builder;

    protected function setUp(): void
    {
        $this->functionProvider = $this->createMock(FunctionProviderInterface::class);
        $this->virtualFieldProvider = $this->createMock(VirtualFieldProviderInterface::class);
        $virtualRelationProvider = $this->createMock(VirtualRelationProviderInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->builder = new ReportDatagridConfigurationBuilder(
            $this->functionProvider,
            $this->virtualFieldProvider,
            $virtualRelationProvider,
            new DoctrineHelper($this->doctrine),
            new DatagridGuesser([]),
            $entityNameResolver
        );

        $dateGroupingBuilder = $this->createMock(DatagridDateGroupingBuilder::class);

        $this->builder->setDateGroupingBuilder($dateGroupingBuilder);
        $this->builder->setConfigManager($this->configManager);

        $this->target = new ReportDatagridConfigurationProvider(
            $this->builder,
            $this->doctrine,
            $this->cache,
            'someKey'
        );
    }

    public function testIsApplicable(): void
    {
        self::assertTrue($this->target->isApplicable(Report::GRID_PREFIX . '1'));
        self::assertFalse($this->target->isApplicable('oro_not_report_table_1'));
        self::assertFalse($this->target->isApplicable('1_oro_report_table_1'));
    }

    public function testIsReportValidForInvalidConfiguration(): void
    {
        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });
        $repository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Report::class)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('find')
            ->with(self::identicalTo(1))
            ->willThrowException(new InvalidConfigurationException());

        self::assertFalse($this->target->isReportValid(Report::GRID_PREFIX . '1'));
    }

    public function testGetConfigurationDoesNotAddActionIfNoRouteConfigured(): void
    {
        $gridName = Report::GRID_PREFIX . '1';
        $entity = Address::class;

        $this->virtualFieldProvider->expects(self::any())
            ->method('isVirtualField')
            ->with($entity, self::anything())
            ->willReturn(false);

        $this->prepareMetadata();

        $report = $this->getReportEntity($entity, ['columns' => [['name' => 'street']]]);
        $this->prepareRepository($report);
        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $configuration = $this->target->getConfiguration($gridName);
        self::assertEmpty($configuration->offsetGetByPath('[actions]'));
    }

    public function testGetConfigurationDoesNotAddActionIfDefinitionHaveGroupingNotByIdentifier(): void
    {
        $gridName = Report::GRID_PREFIX . '1';
        $entity = Address::class;

        $definition = [
            'columns' => [
                ['name' => 'street'],
            ],
            'grouping_columns' => [
                ['name' => 'street'],
            ],
        ];

        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->virtualFieldProvider->expects(self::any())
            ->method('isVirtualField')
            ->with($entity, self::anything())
            ->willReturn(false);

        $metadata = $this->prepareMetadata();

        //only stub
        $metadata->expects(self::any())
            ->method('getIdentifier')
            ->willReturn(['id']);

        $report = $this->getReportEntity($entity, $definition);
        $this->prepareRepository($report);

        $expectedViewRoute = 'oro_sample_view';
        $entityMetadata = $this->getEntityMetadata($expectedViewRoute);

        $this->configManager->expects(self::once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->willReturn($entityMetadata);

        $configuration = $this->target->getConfiguration($gridName);
        self::assertEmpty($configuration->offsetGetByPath('[actions]'));
    }

    public function testGetConfigurationDoesNotAddActionIfDefinitionHaveAggregationFunction(): void
    {
        $gridName = Report::GRID_PREFIX . '1';
        $entity = Address::class;

        $definition = [
            'columns' => [
                [
                    'name' => 'street',
                    'func' => [
                        'name' => 'Sum',
                        'group_type' => 'aggregates',
                        'group_name' => 'number',
                    ],
                ],
            ],
        ];

        $this->functionProvider->expects(self::once())
            ->method('getFunction')
            ->willReturn(['name' => 'Sum', 'expr' => 'SUM($column)']);

        $this->virtualFieldProvider->expects(self::any())
            ->method('isVirtualField')
            ->with($entity, self::anything())
            ->willReturn(false);

        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $metadata = $this->prepareMetadata();

        //only stub
        $metadata->expects(self::any())
            ->method('getIdentifier')
            ->willReturn(['id']);

        $report = $this->getReportEntity($entity, $definition);
        $this->prepareRepository($report);

        $expectedViewRoute = 'oro_sample_view';
        $entityMetadata = $this->getEntityMetadata($expectedViewRoute);

        $this->configManager->expects(self::once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->willReturn($entityMetadata);

        $configuration = $this->target->getConfiguration($gridName);
        self::assertEmpty($configuration->offsetGetByPath('[actions]'));
    }

    public function testGetConfigurationAddAction(): void
    {
        $gridName = Report::GRID_PREFIX . '1';

        $expectedIdName = 'test_id';

        $entity = Address::class;

        $expectedViewRoute = 'oro_sample_view';
        $entityMetadata = $this->getEntityMetadata($expectedViewRoute);

        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $metadata = $this->prepareMetadata();
        $metadata->expects(self::once())
            ->method('getIdentifier')
            ->willReturn([$expectedIdName]);

        $definition = [
            'columns' => [
                [
                    'name' => $expectedIdName,
                ],
                [
                    'name' => 'street',
                    'func' => [
                        'name' => 'Sum',
                        'group_type' => 'aggregates',
                        'group_name' => 'number',
                    ],
                ],
            ],
            'grouping_columns' => [
                ['name' => $expectedIdName],
            ],
        ];

        $this->functionProvider->expects(self::once())
            ->method('getFunction')
            ->willReturn(['name' => 'Sum', 'expr' => 'SUM($column)']);

        $this->virtualFieldProvider->expects(self::any())
            ->method('isVirtualField')
            ->with($entity, self::anything())
            ->willReturn(false);

        $report = $this->getReportEntity($entity, $definition);
        $this->prepareRepository($report);
        $this->configManager->expects(self::once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->willReturn($entityMetadata);

        $configuration = $this->target->getConfiguration($gridName);

        $actualProperties = $configuration->offsetGetByPath('[properties]');
        self::assertArrayHasKey('view_link', $actualProperties);

        $expectedProperties = [
            $expectedIdName => null,
            'view_link' => [
                'type' => 'url',
                'route' => $expectedViewRoute,
                'params' => [$expectedIdName],
            ],
        ];
        self::assertEquals($expectedProperties, $actualProperties);

        $selectParts = $configuration->offsetGetByPath('[source][query][select]');
        $selectIdentifierExist = false;
        foreach ($selectParts as $selectPart) {
            if (str_contains($selectPart, '.' . $expectedIdName)) {
                $selectIdentifierExist = true;
            }
        }
        self::assertTrue($selectIdentifierExist);

        $expectedActions = [
            'view' => [
                'type' => 'navigate',
                'label' => 'oro.report.datagrid.row.action.view',
                'acl_resource' => 'VIEW;entity:Oro\Bundle\AddressBundle\Entity\Address',
                'icon' => 'eye',
                'link' => 'view_link',
                'rowAction' => true,
            ],
        ];
        self::assertEquals($expectedActions, $configuration->offsetGetByPath('[actions]'));
    }

    public function testGetDataFromCache(): void
    {
        $gridName = Report::GRID_PREFIX . '1';

        $expectedIdName = 'test_id';

        $entity = Address::class;

        $expectedViewRoute = 'oro_sample_view';
        $entityMetadata = $this->getEntityMetadata($expectedViewRoute);

        $metadata = $this->prepareMetadata();
        $metadata->expects(self::once())
            ->method('getIdentifier')
            ->willReturn([$expectedIdName]);

        $definition = [
            'columns' => [
                [
                    'name' => $expectedIdName,
                ],
                [
                    'name' => 'street',
                    'func' => [
                        'name' => 'Sum',
                        'group_type' => 'aggregates',
                        'group_name' => 'number',
                    ],
                ],
            ],
            'grouping_columns' => [
                ['name' => $expectedIdName],
            ],
        ];

        $this->functionProvider->expects(self::once())
            ->method('getFunction')
            ->willReturn(['name' => 'Sum', 'expr' => 'SUM($column)']);

        $this->virtualFieldProvider->expects(self::any())
            ->method('isVirtualField')
            ->with($entity, self::anything())
            ->willReturn(false);

        $report = $this->getReportEntity($entity, $definition);

        $this->configManager->expects(self::once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->willReturn($entityMetadata);

        $expectedConfiguration = $this->buildConfiguration($gridName, $report);
        $this->cache->expects(self::once())
            ->method('get')
            ->willReturn($expectedConfiguration);

        $configuration = $this->target->getConfiguration($gridName);

        self::assertEquals($expectedConfiguration, $configuration);
    }

    private function buildConfiguration(string $gridName, Report $report): DatagridConfiguration
    {
        $this->builder->setGridName($gridName);
        $this->builder->setSource($report);

        return $this->builder->getConfiguration();
    }

    private function prepareMetadata(): ClassMetadata|\PHPUnit\Framework\MockObject\MockObject
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($em);
        $em->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        return $metadata;
    }

    private function getReportEntity(
        string $entityClass,
        array $definition
    ): Report|\PHPUnit\Framework\MockObject\MockObject {
        $report = $this->createMock(Report::class);
        $report->expects(self::any())
            ->method('getDefinition')
            ->willReturn(QueryDefinitionUtil::encodeDefinition($definition));
        $report->expects(self::any())
            ->method('getEntity')
            ->willReturn($entityClass);

        return $report;
    }

    /**
     * Initialises repository to return expected report entity
     */
    private function prepareRepository(Report $report): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->willReturn($report);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);
    }

    private function getEntityMetadata(string $viewRoute): EntityMetadata
    {
        $entityMetadata = new EntityMetadata(\stdClass::class);
        $entityMetadata->routeView = $viewRoute;

        return $entityMetadata;
    }
}
