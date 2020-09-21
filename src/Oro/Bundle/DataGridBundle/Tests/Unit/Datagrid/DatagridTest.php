<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CacheBundle\Provider\ArrayCache;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;

class DatagridTest extends \PHPUnit\Framework\TestCase
{
    const TEST_NAME = 'testName';

    /** @var Datagrid */
    protected $grid;

    /** @var Acceptor|\PHPUnit\Framework\MockObject\MockObject */
    protected $acceptor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $parameters;

    /** @var MemoryCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $memoryCacheProvider;

    protected function setUp(): void
    {
        $this->acceptor = $this->getMockBuilder(Acceptor::class)
            ->disableOriginalConstructor()->getMock();

        $this->parameters = new ParameterBag();
        $this->memoryCacheProvider = $this->createMock(MemoryCacheProviderInterface::class);

        $this->grid = new Datagrid(self::TEST_NAME, DatagridConfiguration::create([]), $this->parameters);
        $this->grid->setMemoryCacheProvider($this->memoryCacheProvider);
        $this->grid->setAcceptor($this->acceptor);
    }

    protected function tearDown(): void
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
        $this->memoryCacheProvider->expects($this->once())
            ->method('reset');
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
        /** @var DatasourceInterface|\PHPUnit\Framework\MockObject\MockObject $dataSource */
        $dataSource = $this->createMock(DatasourceInterface::class);

        $rows1 = [
            $this->createMock(ResultRecordInterface::class),
            $this->createMock(ResultRecordInterface::class)
        ];
        $rows2 = [
            $this->createMock(ResultRecordInterface::class)
        ];
        $dataSource->expects($this->exactly(2))
            ->method('getResults')
            ->willReturnOnConsecutiveCalls(
                $rows1,
                $rows2
            );

        $cacheCallCounter = 0;
        $cache = new ArrayCache();
        $this->memoryCacheProvider->expects($this->exactly(3))
            ->method('get')
            ->willReturnCallback(function ($arguments, $callback) use (&$cacheCallCounter, $cache) {
                $this->assertArrayHasKey('datagrid_results', $arguments);
                $this->assertInstanceOf(ParameterBag::class, $arguments['datagrid_results']);
                $cacheCallCounter++;

                $cacheKey = md5(serialize($arguments['datagrid_results']->all()));
                if (!$cache->contains($cacheKey)) {
                    $cache->save($cacheKey, $callback());
                }

                return $cache->fetch($cacheKey);
            });

        $this->grid->setDatasource($dataSource);

        $this->acceptor->expects($this->exactly(2))
            ->method('acceptDatasource')
            ->with($dataSource);
        $this->acceptor->expects($this->exactly(2))
            ->method('acceptResult')
            ->with($this->isInstanceOf(ResultsObject::class));

        $result = $this->grid->getData();
        $this->assertInstanceOf(ResultsObject::class, $result);
        $this->assertEquals($rows1, $result->getData());

        // acceptDatasource() and acceptResult() should not be called any more if parameters not changed
        $result = $this->grid->getData();
        $this->assertInstanceOf(ResultsObject::class, $result);
        $this->assertEquals($rows1, $result->getData());

        $pagerParameters = [
            PagerInterface::PAGE_PARAM     => 1,
            PagerInterface::PER_PAGE_PARAM => 10
        ];
        $this->grid->getParameters()->set(PagerInterface::PAGER_ROOT_PARAM, $pagerParameters);
        // acceptDatasource() and acceptResult() should be called if parameters changed
        $result = $this->grid->getData();
        $this->assertInstanceOf(ResultsObject::class, $result);
        $this->assertEquals($rows2, $result->getData());

        $this->assertEquals(3, $cacheCallCounter);
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
        $this->acceptor->expects($this->once())
            ->method('acceptMetadata')
            ->with($this->isInstanceOf(MetadataObject::class));

        // Check that metadata initialized only once on repeatable calls
        $this->grid->getMetadata();
        $this->assertInstanceOf(MetadataObject::class, $this->grid->getMetadata());
    }

    public function testGetParameters()
    {
        $this->assertSame($this->parameters, $this->grid->getParameters());
    }
}
