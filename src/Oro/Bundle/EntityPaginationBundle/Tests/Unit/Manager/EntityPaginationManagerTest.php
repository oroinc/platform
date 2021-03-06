<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Manager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager;

class EntityPaginationManagerTest extends \PHPUnit\Framework\TestCase
{
    const WRONG_SCOPE = 'wrong_scope';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var EntityPaginationManager */
    protected $entityPaginationManager;

    /** @var \stdClass */
    protected $entity;

    protected function setUp(): void
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityPaginationManager = new EntityPaginationManager($this->configManager);
        $this->entity = new \stdClass();
    }

    /**
     * @param mixed $source
     * @param bool $expected
     * @dataProvider isEnabledDataProvider
     */
    public function testIsEnabled($source, $expected)
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->once())
            ->method('get')
            ->with('oro_entity_pagination.enabled')
            ->will($this->returnValue($source));

        $storage = new EntityPaginationManager($configManager);
        $this->assertSame($expected, $storage->isEnabled());
    }

    /**
     * @return array
     */
    public function isEnabledDataProvider()
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

    public function testGetLimit()
    {
        $limit = 200;

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_entity_pagination.limit')
            ->will($this->returnValue($limit));

        $this->assertEquals($limit, $this->entityPaginationManager->getLimit());
    }

    /**
     * @dataProvider getPermissionProvider
     */
    public function testGetPermission($scope, $expected)
    {
        $result = EntityPaginationManager::getPermission($scope);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function getPermissionProvider()
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

    public function testGetPermissionException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Scope "wrong_scope" is not available.');

        EntityPaginationManager::getPermission(self::WRONG_SCOPE);
    }

    /**
     * @param bool $expected
     * @param bool $isOrmDatasource
     * @param bool|null $entityPagination
     * @dataProvider isDatagridApplicableDataProvider
     */
    public function testIsDatagridApplicable($expected, $isOrmDatasource, $entityPagination)
    {
        if ($isOrmDatasource) {
            $dataSource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
                ->disableOriginalConstructor()
                ->getMock();
        } else {
            $dataSource = $this->createMock('Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface');
        }

        $config = ['options' => ['entity_pagination' => $entityPagination]];
        $configObject = DatagridConfiguration::create($config);

        $dataGrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $dataGrid->expects($this->any())
            ->method('getDatasource')
            ->will($this->returnValue($dataSource));
        $dataGrid->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($configObject));

        $this->assertSame($expected, $this->entityPaginationManager->isDatagridApplicable($dataGrid));
    }

    /**
     * @return array
     */
    public function isDatagridApplicableDataProvider()
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
