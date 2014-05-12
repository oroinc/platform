<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\EventListener;

use Oro\Bundle\TagBundle\EventListener\TagSearchResultsGridListener;

class TagSearchResultsGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $parameters;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $event;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $datagrid;

    /**
     * @var TagSearchResultsGridListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->parameters = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ParameterBag');

        $this->securityProvider = $this->getMockBuilder('Oro\Bundle\TagBundle\Security\SecurityProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->datagrid = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface')
            ->getMock();

        $this->datagrid->expects($this->any())
            ->method('getParameters')
            ->will($this->returnValue($this->parameters));

        $this->event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\BuildAfter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->event->expects($this->once())
            ->method('getDatagrid')
            ->will($this->returnValue($this->datagrid));

        $this->listener = new TagSearchResultsGridListener($this->securityProvider);
    }

    /**
     * @dataProvider allAliasDataProvider
     * @param string $alias
     */
    public function testOnBuildAfterNoAlias($alias)
    {
        $qb = $this->assertOrmDataSource();
        $this->assertAclCall($qb);

        $this->parameters->expects($this->at(1))
            ->method('get')
            ->with('from', '*')
            ->will($this->returnValue($alias));

        $this->listener->onBuildAfter($this->event);
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

        $this->securityProvider->expects($this->once())
            ->method('applyAcl')
            ->with($qb, 'tt');

        $tagId = 100;

        $this->parameters->expects($this->at(0))
            ->method('get')
            ->with('tag_id', 0)
            ->will($this->returnValue($tagId));

        $this->parameters->expects($this->at(1))
            ->method('get')
            ->with('from', '*')
            ->will($this->returnValue('type'));

        $qb->expects($this->at(0))
            ->method('setParameter')
            ->with('tag', $tagId);

        $qb->expects($this->at(1))
            ->method('andWhere')
            ->will($this->returnSelf());

        $qb->expects($this->at(2))
            ->method('setParameter')
            ->with('alias', 'type');

        $this->listener->onBuildAfter($this->event);
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

        return $qb;
    }
}
