<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Content;

use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;
use Oro\Bundle\SyncBundle\Content\DataGridTagListener;
use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;

class DataGridTagListenerTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_GRID_NAME   = 'gridName';
    private const TEST_ENTITY_NAME = 'someEntity';

    /** @var TagGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $generator;

    /** @var DataGridTagListener */
    private $listener;

    protected function setUp(): void
    {
        $this->generator = $this->createMock(TagGeneratorInterface::class);
        $this->listener = new DataGridTagListener($this->generator);
    }

    public function testBuildAfter()
    {
        $config = DatagridConfiguration::createNamed(self::TEST_GRID_NAME, []);
        $acceptor = new Acceptor();
        $acceptor->setConfig($config);
        $parameters = $this->createMock(ParameterBag::class);
        $grid = new Datagrid(self::TEST_GRID_NAME, $config, $parameters);
        $grid->setAcceptor($acceptor);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getDQLPart')
            ->with('from')
            ->willReturn([new From(self::TEST_ENTITY_NAME, 'alias')]);
        $datasourceMock = $this->createMock(OrmDatasource::class);
        $datasourceMock->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($qb);
        $this->generator->expects($this->once())
            ->method('generate')
            ->with(self::TEST_ENTITY_NAME)
            ->willReturn([]);

        $grid->setDatasource($datasourceMock);

        $this->listener->buildAfter(new BuildAfter($grid));

        $this->assertContains(
            'orosync/js/content/grid-builder',
            $config->offsetGetByPath(
                sprintf('%s[%s]', ToolbarExtension::OPTIONS_PATH, MetadataObject::REQUIRED_MODULES_KEY)
            ),
            'Should add js module'
        );
    }
}
