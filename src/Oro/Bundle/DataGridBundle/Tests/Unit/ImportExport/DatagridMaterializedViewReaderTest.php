<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\ImportExport;

use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\Manager as DatagridManager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridMaterializedViewReader;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Component\DoctrineUtils\ORM\Walker\MaterializedViewOutputResultModifier;

class DatagridMaterializedViewReaderTest extends \PHPUnit\Framework\TestCase
{
    private DatagridManager|\PHPUnit\Framework\MockObject\MockObject $datagridManager;

    private DatagridMaterializedViewReader $reader;

    protected function setUp(): void
    {
        $this->datagridManager = $this->createMock(DatagridManager::class);
        $this->reader = new DatagridMaterializedViewReader($this->datagridManager);
    }

    public function testSetImportExportContextWhenNoMaterializedView(): void
    {
        $this->expectExceptionObject(
            new InvalidConfigurationException('Context parameter "materializedViewName" cannot be empty')
        );

        $this->reader->setImportExportContext(new Context([]));
    }

    public function testReadWhenNoContext(): void
    {
        $this->expectExceptionObject(
            new LogicException(
                sprintf(
                    'The export context was expected to be defined at this point. '
                    . 'Make sure %s::setImportExportContext() is called.',
                    DatagridMaterializedViewReader::class
                )
            )
        );

        $this->reader->read();
    }

    public function testReadWhenNoGridName(): void
    {
        $context = new Context(['materializedViewName' => 'sample-name']);

        $this->expectExceptionObject(
            new InvalidConfigurationException('Context parameter "gridName" cannot be empty')
        );

        $this->reader->setImportExportContext($context);
        $this->reader->read();
    }

    public function testRead(): void
    {
        $gridParameters = ['sample-key' => 'sample-value'];
        $datagrid = new Datagrid(
            'sample-datagrid',
            DatagridConfiguration::create([]),
            new ParameterBag($gridParameters)
        );
        $datagrid->setAcceptor(new Acceptor());
        $context = new Context(
            [
                'materializedViewName' => 'sample-name',
                'gridName' => $datagrid->getName(),
                'gridParameters' => $gridParameters,
            ]
        );

        $this->datagridManager
            ->expects(self::once())
            ->method('getDatagrid')
            ->with($context->getOption('gridName'), $context->getOption('gridParameters'))
            ->willReturn($datagrid);

        $ormDatasource = $this->createMock(OrmDatasource::class);
        $datagrid->setDatasource($ormDatasource);

        $records = [new ResultRecord(['column1' => 'row1']), new ResultRecord(['column1' => 'row2'])];
        $ormDatasource
            ->expects(self::once())
            ->method('getResults')
            ->willReturn($records);

        $this->reader->setImportExportContext($context);
        self::assertEquals(0, $context->getReadOffset());
        self::assertEquals(0, $context->getReadCount());
        self::assertEquals($records[0], $this->reader->read());

        self::assertEquals(1, $context->getReadOffset());
        self::assertEquals(1, $context->getReadCount());
        self::assertEquals($records[1], $this->reader->read());

        self::assertEquals(2, $context->getReadOffset());
        self::assertEquals(2, $context->getReadCount());
        self::assertNull($this->reader->read());

        self::assertEquals(2, $context->getReadOffset());
        self::assertEquals(2, $context->getReadCount());

        self::assertEquals(
            [PagerInterface::DISABLED_PARAM => true],
            $datagrid->getParameters()->get(PagerInterface::PAGER_ROOT_PARAM)
        );

        $this->reader->close();
    }

    public function testOnResultsBeforeWhenNoContext(): void
    {
        $event = $this->createMock(OrmResultBefore::class);

        $event
            ->expects(self::never())
            ->method(self::anything());

        $this->reader->onResultBefore($event);
    }

    public function testOnResultsBeforeWhenAnotherGridName(): void
    {
        $gridParameters = ['sample-key' => 'sample-value'];
        $datagrid = new Datagrid(
            'sample-datagrid',
            DatagridConfiguration::create([]),
            new ParameterBag($gridParameters)
        );
        $datagrid->setAcceptor(new Acceptor());
        $context = new Context(
            [
                'materializedViewName' => 'sample-name',
                'gridName' => 'another-grid',
                'gridParameters' => $gridParameters,
                'rowsOffset' => 0,
                'rowsLimit' => 4242,
            ]
        );

        $query = $this->createMock(AbstractQuery::class);
        $query
            ->expects(self::never())
            ->method(self::anything());

        $event = new OrmResultBefore($datagrid, $query);

        $this->reader->setImportExportContext($context);
        $this->reader->onResultBefore($event);
    }

    public function testOnResultsBefore(): void
    {
        $gridParameters = ['sample-key' => 'sample-value'];
        $datagrid = new Datagrid(
            'sample-datagrid',
            DatagridConfiguration::create([]),
            new ParameterBag($gridParameters)
        );
        $datagrid->setAcceptor(new Acceptor());
        $materializedViewName = 'sample-name';
        $rowsOffset = 0;
        $rowsLimit = 4242;
        $context = new Context(
            [
                'materializedViewName' => $materializedViewName,
                'gridName' => $datagrid->getName(),
                'gridParameters' => $gridParameters,
                'rowsOffset' => $rowsOffset,
                'rowsLimit' => $rowsLimit,
            ]
        );

        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setHint'])
            ->addMethods(['setFirstResult', 'setMaxResults'])
            ->getMockForAbstractClass();

        $query
            ->expects(self::once())
            ->method('setHint')
            ->with(MaterializedViewOutputResultModifier::USE_MATERIALIZED_VIEW, $materializedViewName)
            ->willReturnSelf();

        $query
            ->expects(self::once())
            ->method('setFirstResult')
            ->with($rowsOffset)
            ->willReturnSelf();

        $query
            ->expects(self::once())
            ->method('setMaxResults')
            ->with($rowsLimit)
            ->willReturnSelf();

        $event = new OrmResultBefore($datagrid, $query);

        $this->reader->setImportExportContext($context);
        $this->reader->onResultBefore($event);
    }
}
