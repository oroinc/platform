<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;

class DatagridTest extends \PHPUnit\Framework\TestCase
{
    const TEST_NAME = 'testName';

    /** @var Datagrid */
    protected $grid;

    /** @var Acceptor|\PHPUnit\Framework\MockObject\MockObject */
    protected $acceptor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $parameters;

    protected function setUp()
    {
        $this->acceptor = $this->getMockBuilder(Acceptor::class)
            ->disableOriginalConstructor()->getMock();

        $this->parameters = $this->createMock(ParameterBag::class);

        $this->grid = new Datagrid(self::TEST_NAME, DatagridConfiguration::create([]), $this->parameters);
        $this->grid->setAcceptor($this->acceptor);
    }

    protected function tearDown()
    {
        unset($this->acceptor);
        unset($this->grid);
    }

    /**
     * Test method getName
     */
    public function testGetName()
    {
        $this->assertEquals(self::TEST_NAME, $this->grid->getName());
    }

    /**
     * Test methods setDatasource, getDatasource
     */
    public function testSetDatasource()
    {
        /** @var DatasourceInterface|\PHPUnit\Framework\MockObject\MockBuilder $dataSource */
        $dataSource = $this->getMockForAbstractClass(DatasourceInterface::class);

        $this->assertNull($this->grid->getDatasource());
        $this->grid->setDatasource($dataSource);

        $this->assertSame($dataSource, $this->grid->getDatasource());
    }

    /**
     * Test methods setAcceptor, getAcceptor
     */
    public function testSetAcceptor()
    {
        $anotherOneAcceptor = clone $this->acceptor;

        $this->assertSame($this->acceptor, $this->grid->getAcceptor());
        $this->assertNotSame($anotherOneAcceptor, $this->grid->getAcceptor());

        $this->grid->setAcceptor($anotherOneAcceptor);

        $this->assertSame($anotherOneAcceptor, $this->grid->getAcceptor());
        $this->assertNotSame($this->acceptor, $this->grid->getAcceptor());
    }

    /**
     * Test method getData
     */
    public function testGetData()
    {
        /** @var DatasourceInterface|\PHPUnit\Framework\MockObject\MockBuilder $dataSource */
        $dataSource = $this->getMockForAbstractClass(DatasourceInterface::class);
        $this->grid->setDatasource($dataSource);

        $this->acceptor->expects($this->once())->method('acceptDatasource')
            ->with($dataSource);
        $this->acceptor->expects($this->once())->method('acceptResult')
            ->with($this->isInstanceOf(ResultsObject::class));

        $result = $this->grid->getData();
        $this->assertInstanceOf(ResultsObject::class, $result);

        // acceptDatasource() and acceptResult() should not be called any more
        $result = $this->grid->getData();
        $this->assertInstanceOf(ResultsObject::class, $result);
    }

    /**
     * Test method getAcceptedDataSource
     */
    public function testGetAcceptedDataSource()
    {
        /** @var DatasourceInterface|\PHPUnit\Framework\MockObject\MockBuilder $dataSource */
        $dataSource = $this->getMockForAbstractClass(DatasourceInterface::class);
        $this->grid->setDatasource($dataSource);

        $this->acceptor->expects($this->once())->method('acceptDatasource')
            ->with($dataSource);

        $result = $this->grid->getAcceptedDatasource();
        $this->assertEquals($dataSource, $result);
    }

    /**
     * Test method getMetaData
     */
    public function testGetMetaData()
    {
        $this->acceptor->expects($this->once())->method('acceptMetadata')
            ->with($this->isInstanceOf(MetadataObject::class));

        $result = $this->grid->getMetadata();
        $this->assertInstanceOf(MetadataObject::class, $result);
    }

    public function testGetParameters()
    {
        $this->assertSame($this->parameters, $this->grid->getParameters());
    }
}
