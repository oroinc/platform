<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\EventListener\AbstractConfigGridListener;
use Oro\Bundle\EntityConfigBundle\EventListener\FieldConfigGridListener;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldConfigGridListenerTest extends TestCase
{
    private FieldConfigGridListener $listener;
    private ConfigManager|MockObject $configManager;
    private SystemAwareResolver|MockObject $datagridResolver;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager->expects(self::any())
            ->method('getProviders')
            ->willReturn([]);
        $this->datagridResolver = $this->createMock(SystemAwareResolver::class);
        $this->listener = new FieldConfigGridListener(
            $this->configManager,
            $this->datagridResolver
        );
    }

    public function testOnBuildAfter(): void
    {
        $datagrid = $this->getDatagridMock(1);

        $event = new BuildAfter($datagrid);

        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildBefore(): void
    {
        $datagrid = $this->getDatagridMock(1);
        $gridName = 'datagrid_name';
        $columnSettings = [
            'columns' => [],
            'sorters' => [],
            'filters' => [],
        ];

        $datagridConfiguration = $this->createMock(DatagridConfiguration::class);
        $datagridConfiguration->expects(self::once())
            ->method('getName')
            ->willReturn($gridName);
        $datagridConfiguration->expects(self::exactly(5))
            ->method('offsetGetByPath')
            ->withConsecutive(
                [AbstractConfigGridListener::PATH_COLUMNS, []],
                [AbstractConfigGridListener::PATH_SORTERS, []],
                [AbstractConfigGridListener::PATH_FILTERS, []],
                [AbstractConfigGridListener::PATH_COLUMNS, []],
                [AbstractConfigGridListener::PATH_ACTIONS, []],
            )
            ->willReturn([]);
        $datagridConfiguration->expects(self::exactly(4))
            ->method('offsetSetByPath')
            ->withConsecutive(
                [AbstractConfigGridListener::PATH_COLUMNS, []],
                [AbstractConfigGridListener::PATH_SORTERS, []],
                [AbstractConfigGridListener::PATH_FILTERS, []],
                [AbstractConfigGridListener::PATH_ACTIONS, []],
            );

        $this->datagridResolver->expects(self::once())
            ->method('resolve')
            ->with($gridName, $columnSettings)
            ->willReturn($columnSettings);

        $event = new BuildBefore($datagrid, $datagridConfiguration);

        $this->listener->onBuildBefore($event);
    }

    public function testOnOrmResultAfterFiltersAllHiddenTargets(): void
    {
        $records = [
            $this->createResultRecord('TestClass1', 'field1', 1),
            $this->createResultRecord('TestClass2', 'field2', 2),
            $this->createResultRecord('TestClass3', 'field3', 3),
        ];

        $this->setupConfigProviderExpectations([
            [['TestClass1', 'field1'], 'HiddenEntity1', true, true],
            [['TestClass2', 'field2'], 'HiddenEntity2', true, true],
            [['TestClass3', 'field3'], 'HiddenEntity3', true, true],
        ]);

        $event = new OrmResultAfter($this->getDatagridMock(1), $records);
        $this->listener->onOrmResultAfter($event);

        self::assertEmpty($event->getRecords());
    }

    public function testOnOrmResultAfterFiltersOneHiddenTargets(): void
    {
        $records = [
            $this->createResultRecord('TestClass1', 'field1', 1),
            $this->createResultRecord('TestClass2', 'field2', 2),
            $this->createResultRecord('TestClass3', 'field3', 3),
        ];

        $this->setupConfigProviderExpectations([
            [['TestClass1', 'field1'], 'HiddenEntity1', true, true],
            [['TestClass2', 'field2'], 'NotHiddenEntity2', true, false],
            [['TestClass3', 'field3'], 'NotHiddenEntity3', true, false],
        ]);

        $event = new OrmResultAfter($this->getDatagridMock(1), $records);
        $this->listener->onOrmResultAfter($event);

        self::assertCount(2, $event->getRecords());
    }

    private function createResultRecord(string $className, string $fieldName, int $id): ResultRecord
    {
        return new ResultRecord([
            'id' => $id,
            'className' => $className,
            'fieldName' => $fieldName,
        ]);
    }

    private function setupConfigProviderExpectations(array $expectations): void
    {
        $entitiesFields = array_column($expectations, 0);
        $targets = array_column($expectations, 1);
        $hasConfigs = array_column($expectations, 2);
        $isHiddenModels = array_column($expectations, 3);
        $countCalls = count($expectations);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::exactly($countCalls))
            ->method('get')
            ->with('target_entity', false, null)
            ->willReturnOnConsecutiveCalls(...$targets);

        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects(self::exactly($countCalls))
            ->method('hasConfig')
            ->withConsecutive(...$entitiesFields)
            ->willReturnOnConsecutiveCalls(...$hasConfigs);
        $configProvider->expects(self::exactly($countCalls))
            ->method('getConfig')
            ->withConsecutive(...$entitiesFields)
            ->willReturn($config);

        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($configProvider);
        $this->configManager->expects(self::exactly($countCalls))
            ->method('isHiddenModel')
            ->willReturnOnConsecutiveCalls(...$isHiddenModels);
    }

    private function getDatagridMock(int $entityId): DatagridInterface&MockObject
    {
        $parameters = new ParameterBag();
        $parameters->set(FieldConfigGridListener::ENTITY_PARAM, $entityId);

        $queryBilder = $this->createMock(QueryBuilder::class);
        $queryBilder->expects(self::any())
            ->method('setParameter')
            ->with('entity_id', $entityId);

        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects(self::any())
            ->method('getQueryBuilder')
            ->willReturn($queryBilder);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects(self::any())
            ->method('getDatasource')
            ->willReturn($dataSource);
        $datagrid->expects(self::any())
            ->method('getParameters')
            ->willReturn($parameters);

        return $datagrid;
    }
}
