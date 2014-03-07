<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Content;

use Doctrine\ORM\Query\Expr\From;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;
use Oro\Bundle\NavigationBundle\Content\DataGridTagListener;
use Oro\Bundle\NavigationBundle\Content\TagGeneratorChain;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class DataGridTagListenerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_GRID_NAME   = 'gridName';
    const TEST_ENTITY_NAME = 'someEntity';

    /** @var TagGeneratorChain|\PHPUnit_Framework_MockObject_MockObject */
    protected $generator;

    /** @var DataGridTagListener */
    protected $listener;

    public function setUp()
    {
        $this->generator = $this->getMock('Oro\Bundle\NavigationBundle\Content\TagGeneratorChain');
        $this->listener  = new DataGridTagListener($this->generator);
    }

    public function tearDown()
    {
        unset($this->generator, $this->listener);
    }

    public function testBuildAfter()
    {
        $config   = DatagridConfiguration::createNamed(self::TEST_GRID_NAME, []);
        $acceptor = new Acceptor($config);
        $grid     = new DataGrid(self::TEST_GRID_NAME, $acceptor);

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
            'oronavigation/js/content/grid-builder',
            $config->offsetGetByPath(
                sprintf('%s[%s]', ToolbarExtension::OPTIONS_PATH, MetadataObject::REQUIRED_MODULES_KEY)
            ),
            'Should add require js module'
        );
    }
}
