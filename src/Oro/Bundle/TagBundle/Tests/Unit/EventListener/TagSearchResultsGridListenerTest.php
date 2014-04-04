<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\EventListener;

use Oro\Bundle\TagBundle\EventListener\TagSearchResultsGridListener;

class TagSearchResultsGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $requestParams;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $event;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $datagrid;

    protected function setUp()
    {
        $this->requestParams = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\RequestParameters')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityProvider = $this->getMockBuilder('Oro\Bundle\TagBundle\Security\SecurityProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->datagrid = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface')
            ->getMock();

        $this->event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\BuildAfter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event->expects($this->once())
            ->method('getDatagrid')
            ->will($this->returnValue($this->datagrid));
    }

    /**
     * @dataProvider allAliasDataProvider
     * @param string $alias
     */
    public function testOnBuildAfterNoAlias($alias)
    {
        $qb = $this->assertOrmDataSource();
        $this->assertAclCall($qb);

        $this->requestParams->expects($this->at(1))
            ->method('get')
            ->with('from', '*')
            ->will($this->returnValue($alias));

        $listener = new TagSearchResultsGridListener($this->requestParams, $this->securityProvider);
        $listener->onBuildAfter($this->event);
    }

    public function allAliasDataProvider()
    {
        return array(
            array(''),
            array(null),
            array('*')
        );
    }

    public function testOnBuildAfterWithAlias()
    {
        $qb = $this->assertOrmDataSource();
        $this->assertAclCall($qb);

        $this->requestParams->expects($this->at(1))
            ->method('get')
            ->with('from', '*')
            ->will($this->returnValue('type'));

        $qb->expects($this->once())
            ->method('andWhere');
        $qb->expects($this->at(2))
            ->method('setParameter')
            ->with('alias', 'type');

        $listener = new TagSearchResultsGridListener($this->requestParams, $this->securityProvider);
        $listener->onBuildAfter($this->event);
    }

    protected function assertAclCall($qb)
    {
        $this->securityProvider->expects($this->once())
            ->method('applyAcl')
            ->with($qb, 'tt');
    }

    protected function assertOrmDataSource()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));

        $this->requestParams->expects($this->at(0))
            ->method('get')
            ->with('tag_id', 0)
            ->will($this->returnValue('test'));

        $qb->expects($this->any())
            ->method($this->anything())
            ->will($this->returnSelf());
        $qb->expects($this->at(0))
            ->method('setParameter')
            ->with('tag', 'test');

        return $qb;
    }
}
