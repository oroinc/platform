<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\TranslationBundle\Provider\OroTranslationAdapter;
use Oro\Bundle\TranslationBundle\Provider\PackagesProvider;
use Oro\Bundle\TranslationBundle\Provider\TranslationStatisticProvider;
use Psr\Log\LoggerInterface;

class TranslationStatisticProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    protected $cache;

    /** @var OroTranslationAdapter|\PHPUnit\Framework\MockObject\MockObject */
    protected $adapter;

    /** @var PackagesProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $packagesProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var TranslationStatisticProvider */
    protected $provider;

    protected function setUp()
    {
        $this->cache            = $this->createMock('Doctrine\Common\Cache\Cache');
        $this->adapter          = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Provider\OroTranslationAdapter')
            ->disableOriginalConstructor()->getMock();
        $this->packagesProvider = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Provider\PackagesProvider')
            ->disableOriginalConstructor()->getMock();
        $this->packagesProvider->expects($this->any())->method('getInstalledPackages')
            ->will($this->returnValue([]));
        $this->logger           = $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()->getMock();

        $this->provider = new TranslationStatisticProvider(
            $this->cache,
            $this->adapter,
            $this->packagesProvider,
            $this->logger
        );
    }

    protected function tearDown()
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
     * @param mixed      $cachedData
     * @param array      $resultExpected
     * @param bool       $fetchExpected
     * @param array      $fetchedResult
     * @param \Exception $exception
     *
     */
    public function testGet(
        $cachedData,
        $resultExpected,
        $fetchExpected,
        $fetchedResult = [],
        \Exception $exception = null
    ) {
        $this->cache->expects($this->once())->method('fetch')
            ->with($this->equalTo(TranslationStatisticProvider::CACHE_KEY))
            ->will($this->returnValue($cachedData));

        if ($fetchExpected) {
            if (null !== $exception) {
                $this->adapter->expects($this->once())->method('fetchStatistic')
                    ->will($this->throwException($exception));
            } else {
                $this->adapter->expects($this->once())->method('fetchStatistic')
                    ->will($this->returnValue($fetchedResult));
            }
            if (!empty($fetchedResult)) {
                $this->cache->expects($this->once())->method('save')
                    ->with($this->equalTo(TranslationStatisticProvider::CACHE_KEY));
            }
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
            'no cache data, fetch expected'            => [false, $testDataSet, true, $testDataSet],
            'cache data found , no fetch needed'       => [$testDataSet, $testDataSet, false],
            'exception should be caught no data saved' => [false, [], true, [], new \Exception()]
        ];
    }
}
