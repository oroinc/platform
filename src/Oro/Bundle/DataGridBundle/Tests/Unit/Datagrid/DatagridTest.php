<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

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
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class DatagridTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_NAME = 'testName';

    /** @var Acceptor|\PHPUnit\Framework\MockObject\MockObject */
    private $acceptor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $parameters;

    /** @var MemoryCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $memoryCacheProvider;

    /** @var Datagrid */
    private $grid;

    protected function setUp(): void
    {
        $this->acceptor = $this->createMock(Acceptor::class);
        $this->parameters = new ParameterBag();
        $this->memoryCacheProvider = $this->createMock(MemoryCacheProviderInterface::class);

        $this->grid = new Datagrid(self::TEST_NAME, DatagridConfiguration::create([]), $this->parameters);
        $this->grid->setMemoryCacheProvider($this->memoryCacheProvider);
        $this->grid->setAcceptor($this->acceptor);
    }

    public function testGetName()
    {
        $this->assertEquals(self::TEST_NAME, $this->grid->getName());
    }

    public function testSetAndGetDatasource()
    {
        $this->memoryCacheProvider->expects($this->once())
            ->method('reset');
        $dataSource = $this->createMock(DatasourceInterface::class);

        $this->assertNull($this->grid->getDatasource());
        $this->grid->setDatasource($dataSource);

        $this->assertSame($dataSource, $this->grid->getDatasource());
    }

    public function testSetAndGetAcceptor()
    {
        $anotherOneAcceptor = clone $this->acceptor;

        $this->assertSame($this->acceptor, $this->grid->getAcceptor());
        $this->assertNotSame($anotherOneAcceptor, $this->grid->getAcceptor());

        $this->grid->setAcceptor($anotherOneAcceptor);

        $this->assertSame($anotherOneAcceptor, $this->grid->getAcceptor());
        $this->assertNotSame($this->acceptor, $this->grid->getAcceptor());
    }

    public function testGetData()
    {
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
            ->willReturnOnConsecutiveCalls($rows1, $rows2);

        $cacheCallCounter = 0;
        $cache = new ArrayAdapter(0, false);
        $this->memoryCacheProvider->expects($this->exactly(3))
            ->method('get')
            ->willReturnCallback(function ($arguments, $callback) use (&$cacheCallCounter, $cache) {
                $this->assertArrayHasKey('datagrid_results', $arguments);
                $this->assertInstanceOf(ParameterBag::class, $arguments['datagrid_results']);
                $cacheCallCounter++;

                $cacheKey = md5(serialize($arguments['datagrid_results']->all()));
                $cacheItem = $cache->getItem($cacheKey);
                if (!$cacheItem->isHit()) {
                    $cache->save($cacheItem->set($callback()));
                }
                return $cacheItem->get();
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

    public function testGetAcceptedDataSource()
    {
        $dataSource = $this->createMock(DatasourceInterface::class);
        $this->grid->setDatasource($dataSource);

        $this->acceptor->expects($this->once())
            ->method('acceptDatasource')
            ->with($dataSource);

        $result = $this->grid->getAcceptedDatasource();
        $this->assertEquals($dataSource, $result);
    }

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
