<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Content;

use Doctrine\ORM\Query\Expr\From;

use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\SyncBundle\Content\DataGridTagListener;
use Oro\Bundle\SyncBundle\Content\TagGeneratorChain;

class DataGridTagListenerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_GRID_NAME   = 'gridName';
    const TEST_ENTITY_NAME = 'someEntity';

    /** @var TagGeneratorChain|\PHPUnit_Framework_MockObject_MockObject */
    protected $generator;

    /** @var DataGridTagListener */
    protected $listener;

    protected function setUp()
    {
        $this->generator = $this->getMock('Oro\Bundle\SyncBundle\Content\TagGeneratorChain');
        $this->listener  = new DataGridTagListener($this->generator);
    }

    protected function tearDown()
    {
        unset($this->generator, $this->listener);
    }

    public function testBuildAfter()
    {
        $config     = DatagridConfiguration::createNamed(self::TEST_GRID_NAME, []);
        $acceptor   = new Acceptor();
        $acceptor->setConfig($config);
        $parameters = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ParameterBag');
        $grid       = new Datagrid(self::TEST_GRID_NAME, $config, $parameters);
        $grid->setAcceptor($acceptor);

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();
        $qb->expects($this->once())->method('getDQLPart')->with($this->equalTo('from'))
            ->will($this->returnValue([new From(self::TEST_ENTITY_NAME, 'alias')]));
        $datasourceMock = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()->getMock();
        $datasourceMock->expects($this->any())->method('getQueryBuilder')
            ->will($this->returnValue($qb));
        $this->generator->expects($this->once())->method('generate')->with(self::TEST_ENTITY_NAME)
            ->will($this->returnValue([]));

        $grid->setDatasource($datasourceMock);
        $event = new BuildAfter($grid);

        $this->listener->buildAfter($event);

        $this->assertContains(
            'orosync/js/content/grid-builder',
            $config->offsetGetByPath(
                sprintf('%s[%s]', ToolbarExtension::OPTIONS_PATH, MetadataObject::REQUIRED_MODULES_KEY)
            ),
            'Should add require js module'
        );
    }
}
