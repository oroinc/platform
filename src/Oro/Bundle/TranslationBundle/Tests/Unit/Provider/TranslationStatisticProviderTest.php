<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\Cache;

use Oro\Bundle\TranslationBundle\Provider\OroTranslationAdapter;
use Oro\Bundle\TranslationBundle\Provider\PackagesProvider;
use Oro\Bundle\TranslationBundle\Provider\TranslationStatisticProvider;

class TranslationStatisticProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var Cache|\PHPUnit_Framework_MockObject_MockObject */
    protected $cache;

    /** @var OroTranslationAdapter|\PHPUnit_Framework_MockObject_MockObject */
    protected $adapter;

    /** @var PackagesProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $packagesProvider;

    /** @var TranslationStatisticProvider */
    protected $provider;

    public function setUp()
    {
        $this->cache            = $this->getMock('Doctrine\Common\Cache\Cache');
        $this->adapter          = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Provider\OroTranslationAdapter')
            ->disableOriginalConstructor()->getMock();
        $this->packagesProvider = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Provider\PackagesProvider')
            ->disableOriginalConstructor()->getMock();
        $this->packagesProvider->expects($this->any())->method('getInstalledPackages')
            ->will($this->returnValue([]));

        $this->provider = new TranslationStatisticProvider($this->cache, $this->adapter, $this->packagesProvider);
    }

    public function tearDown()
    {
        unset($this->cache, $this->adapter, $this->packagesProvider, $this->provider);
    }

    public function testClear()
    {
        $this->cache->expects($this->once())->method('delete')
            ->with($this->equalTo(TranslationStatisticProvider::CACHE_KEY));

        $this->provider->clear();
    }

    /**
     * @dataProvider getProvider
     *
     * @param mixed $cachedData
     * @param array $resultExpected
     * @param bool  $fetchExpected
     * @param array $fetchedResult
     * @param bool  $isException
     */
    public function testGet($cachedData, $resultExpected, $fetchExpected, $fetchedResult = [], $isException = false)
    {
        $this->cache->expects($this->once())->method('fetch')
            ->with($this->equalTo(TranslationStatisticProvider::CACHE_KEY))
            ->will($this->returnValue($cachedData));

        if ($fetchExpected) {
            if ($isException) {
                $this->adapter->expects($this->once())->method('fetchStatistic')
                    ->will($this->throwException($fetchedResult));
            } else {
                $this->adapter->expects($this->once())->method('fetchStatistic')
                    ->will($this->returnValue($fetchedResult));
            }

            $this->cache->expects($this->once())->method('save')
                ->with($this->equalTo(TranslationStatisticProvider::CACHE_KEY));
        } else {
            $this->adapter->expects($this->never())->method('fetchStatistic');
        }

        $result = $this->provider->get();
        $this->assertSame($resultExpected, $result);
    }

    /**
     * @return array
     */
    public function getProvider()
    {
        $testDataSet = [['code' => 'en']];

        return [
            'no cache data, fetch expected'      => [false, $testDataSet, true, $testDataSet],
            'cache data found , no fetch needed' => [$testDataSet, $testDataSet, false],
            'exception should be caught'         => [false, [], true, new \Exception(), true]
        ];
    }
}
