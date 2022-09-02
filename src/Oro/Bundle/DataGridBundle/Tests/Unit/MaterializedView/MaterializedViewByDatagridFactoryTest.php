<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\MaterializedView;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\MaterializedView\MaterializedViewByDatagridFactory;
use Oro\Bundle\PlatformBundle\Entity\MaterializedView;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewManager;

class MaterializedViewByDatagridFactoryTest extends \PHPUnit\Framework\TestCase
{
    private MaterializedViewManager|\PHPUnit\Framework\MockObject\MockObject $materializedViewManager;

    private MaterializedViewByDatagridFactory $factory;

    protected function setUp(): void
    {
        $this->materializedViewManager = $this->createMock(MaterializedViewManager::class);
        $this->factory = new MaterializedViewByDatagridFactory($this->materializedViewManager);
    }

    public function testCreateByDatagridWhenNotOrmDatasource(): void
    {
        $datagrid = new Datagrid(
            'sample-datagrid',
            DatagridConfiguration::create([]),
            new ParameterBag()
        );
        $datagrid->setAcceptor(new Acceptor());
        $datasource = new ArrayDatasource();
        $datagrid->setDatasource($datasource);

        $this->expectExceptionObject(
            new \LogicException(
                sprintf(
                    'Datasource was expected to be an instance of %s, got %s for the datagrid %s',
                    OrmDatasource::class,
                    get_class($datasource),
                    $datagrid->getName()
                )
            )
        );

        $this->factory->createByDatagrid($datagrid);
    }

    public function testCreateByDatagrid(): void
    {
        $datagrid = new Datagrid(
            'sample-datagrid',
            DatagridConfiguration::create([]),
            new ParameterBag()
        );
        $datagrid->setAcceptor(new Acceptor());
        $datasource = $this->createMock(OrmDatasource::class);
        $datagrid->setDatasource($datasource);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new Configuration());

        $query = new Query($entityManager);
        $datasource
            ->expects(self::once())
            ->method('getResultsQuery')
            ->willReturn($query);

        $materializedView = new MaterializedView();
        $this->materializedViewManager
            ->expects(self::once())
            ->method('createByQuery')
            ->with($query)
            ->willReturn($materializedView);

        self::assertEquals($materializedView, $this->factory->createByDatagrid($datagrid));
    }
}
