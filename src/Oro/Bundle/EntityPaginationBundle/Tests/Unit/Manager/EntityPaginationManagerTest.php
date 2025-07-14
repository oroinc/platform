<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityPaginationManagerTest extends TestCase
{
    private const WRONG_SCOPE = 'wrong_scope';

    private ConfigManager&MockObject $configManager;
    private EntityPaginationManager $entityPaginationManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->entityPaginationManager = new EntityPaginationManager($this->configManager);
    }

    /**
     * @dataProvider isEnabledDataProvider
     */
    public function testIsEnabled(string|bool|null $source, bool $expected): void
    {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->once())
            ->method('get')
            ->with('oro_entity_pagination.enabled')
            ->willReturn($source);

        $storage = new EntityPaginationManager($configManager);
        $this->assertSame($expected, $storage->isEnabled());
    }

    public function isEnabledDataProvider(): array
    {
        return [
            'string true' => [
                'source'   => '1',
                'expected' => true,
            ],
            'string false' => [
                'source'   => '0',
                'expected' => false,
            ],
            'boolean true' => [
                'source'   => true,
                'expected' => true,
            ],
            'boolean false' => [
                'source'   => false,
                'expected' => false,
            ],
            'null' => [
                'source'   => null,
                'expected' => false,
            ],
        ];
    }

    public function testGetLimit(): void
    {
        $limit = 200;

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_entity_pagination.limit')
            ->willReturn($limit);

        $this->assertEquals($limit, $this->entityPaginationManager->getLimit());
    }

    /**
     * @dataProvider getPermissionProvider
     */
    public function testGetPermission(string $scope, string $expected): void
    {
        $result = EntityPaginationManager::getPermission($scope);
        $this->assertSame($expected, $result);
    }

    public function getPermissionProvider(): array
    {
        return [
            'view scope' => [
                'scope'    => EntityPaginationManager::VIEW_SCOPE,
                'expected' => 'VIEW'
            ],
            'edit scope' => [
                'scope'    => EntityPaginationManager::EDIT_SCOPE,
                'expected' => 'EDIT'
            ],
        ];
    }

    public function testGetPermissionException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Scope "wrong_scope" is not available.');

        EntityPaginationManager::getPermission(self::WRONG_SCOPE);
    }

    /**
     * @dataProvider isDatagridApplicableDataProvider
     */
    public function testIsDatagridApplicable(bool $expected, bool $isOrmDatasource, ?bool $entityPagination): void
    {
        if ($isOrmDatasource) {
            $dataSource = $this->createMock(OrmDatasource::class);
        } else {
            $dataSource = $this->createMock(DatasourceInterface::class);
        }

        $config = ['options' => ['entity_pagination' => $entityPagination]];
        $configObject = DatagridConfiguration::create($config);

        $dataGrid = $this->createMock(DatagridInterface::class);
        $dataGrid->expects($this->any())
            ->method('getDatasource')
            ->willReturn($dataSource);
        $dataGrid->expects($this->any())
            ->method('getConfig')
            ->willReturn($configObject);

        $this->assertSame($expected, $this->entityPaginationManager->isDatagridApplicable($dataGrid));
    }

    public function isDatagridApplicableDataProvider(): array
    {
        return [
            'not orm datasource' => [
                'expected' => false,
                'isOrmDatasource' => false,
                'entityPagination' => true,
            ],
            'pagination not specified' => [
                'expected' => false,
                'isOrmDatasource' => true,
                'entityPagination' => null,
            ],
            'pagination disabled' => [
                'expected' => false,
                'isOrmDatasource' => true,
                'entityPagination' => false,
            ],
            'pagination enabled' => [
                'expected' => true,
                'isOrmDatasource' => true,
                'entityPagination' => true,
            ],
        ];
    }
}
