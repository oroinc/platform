<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityPaginationBundle\EventListener\EntityPaginationListener;

class EntityPaginationListenerTest extends \PHPUnit\Framework\TestCase
{
    const ENTITY_NAME = 'test_entity';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $storage;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $paginationManager;

    /**
     * @var EntityPaginationListener
     */
    protected $listener;

    public function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->storage = $this->getMockBuilder('Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paginationManager =
            $this->getMockBuilder('Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager')
                ->disableOriginalConstructor()
                ->getMock();

        $this->listener = new EntityPaginationListener($this->doctrineHelper, $this->storage, $this->paginationManager);
    }

    public function testOnResultAfterSystemPaginationDisabled()
    {
        $this->paginationManager->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(false));
        $this->paginationManager->expects($this->never())
            ->method('isDatagridApplicable');

        $this->listener->onResultAfter(new OrmResultAfter($this->createGridMock()));
    }

    public function testOnResultAfterGridNotApplicable()
    {
        $this->paginationManager->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->paginationManager->expects($this->once())
            ->method('isDatagridApplicable')
            ->will($this->returnValue(false));
        $this->storage->expects($this->never())
            ->method('clearData');

        $this->listener->onResultAfter(new OrmResultAfter($this->createGridMock()));
    }

    public function testOnResultClearData()
    {
        $this->paginationManager->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->paginationManager->expects($this->once())
            ->method('isDatagridApplicable')
            ->will($this->returnValue(true));
        $this->storage->expects($this->once())
            ->method('clearData')
            ->with(self::ENTITY_NAME);

        $this->listener->onResultAfter(new OrmResultAfter($this->createGridMock()));
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createGridMock()
    {
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder->expects($this->any())
            ->method('getRootEntities')
            ->will($this->returnValue([self::ENTITY_NAME]));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityMetadata')
            ->with(self::ENTITY_NAME)
            ->will($this->returnValue(new ClassMetadata(self::ENTITY_NAME)));

        $dataSource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $dataSource->expects($this->any())
            ->method('getQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $dataGrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $dataGrid->expects($this->any())
            ->method('getDatasource')
            ->will($this->returnValue($dataSource));

        return $dataGrid;
    }
}
