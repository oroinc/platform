<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\TagBundle\EventListener\TagSearchResultsGridListener;
use Oro\Bundle\TagBundle\Security\SecurityProvider;

class TagSearchResultsGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $parameters;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityProvider */
    protected $securityProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $event;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $datagrid;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityAliasResolver */
    protected $entityAliasResolver;

    /** @var TagSearchResultsGridListener */
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

        $this->entityAliasResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new TagSearchResultsGridListener($this->securityProvider, $this->entityAliasResolver);
    }

    /**
     * @dataProvider testOnBuildAfterDataProvider
     *
     * @param string                       $alias
     * @param string|null                  $entityClass
     * @param EntityAliasNotFoundException $exception
     */
    public function testOnBuildAfter($alias, $entityClass = null, EntityAliasNotFoundException $exception = null)
    {
        $this->event->expects($this->once())
            ->method('getDatagrid')
            ->will($this->returnValue($this->datagrid));

        $qb = $this->assertOrmDataSource();
        $this->assertAclCall($qb);
        $tagId = 100;

        $this->parameters->expects($this->at(0))
            ->method('get')
            ->with('tag_id', 0)
            ->will($this->returnValue($tagId));

        $qb
            ->expects($this->at(0))
            ->method('setParameter')
            ->with('tag', $tagId);

        $this->parameters->expects($this->at(1))
            ->method('get')
            ->with('from', '')
            ->will($this->returnValue($alias));

        if (strlen($alias) > 0) {
            $earMockBuilder = $this->entityAliasResolver
                ->expects($this->once())
                ->method('getClassByAlias')
                ->with($alias);
            if (null !== $entityClass) {
                $earMockBuilder->willReturn($entityClass);
                $qb
                    ->expects($this->at(1))
                    ->method('andWhere')
                    ->with('tt.entityName = :entityClass')
                    ->willReturn($qb);
                $qb
                    ->expects($this->at(2))
                    ->method('setParameter')
                    ->with('entityClass', $entityClass);
            } else {
                $earMockBuilder->willThrowException($exception);
                $qb
                    ->expects($this->at(1))
                    ->method('andWhere')
                    ->with('1 = 0');
            }
        }

        $this->listener->onBuildAfter($this->event);
    }

    public function testOnBuildAfterDataProvider()
    {
        return [
            'empty parameter' => [''],
            'existing alias' => ['testalias', 'testEntity'],
            'not existing alias' => ['testalias', null, new EntityAliasNotFoundException()]
        ];
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
