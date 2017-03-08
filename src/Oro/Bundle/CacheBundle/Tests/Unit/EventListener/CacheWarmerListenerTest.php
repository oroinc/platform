<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\EventListener;

use Oro\Bundle\CacheBundle\EventListener\CacheWarmerListener;
use Oro\Bundle\CacheBundle\Provider\ConfigCacheWarmerInterface;
use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;

class CacheWarmerListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var CacheWarmerListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->listener = new CacheWarmerListener(true);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->listener);
    }

    /**
     * @dataProvider cacheMapProvider
     *
     * @param array $cacheMap
     */
    public function testOnKernelRequest(array $cacheMap)
    {
        foreach ($cacheMap as $item) {
            $this->listener->addConfigCacheProvider($item['configCacheWarmer'], $item['configMetadataDumper']);
        }

        $this->listener->onKernelRequest();
    }

    /**
     * @return \Generator
     */
    public function cacheMapProvider()
    {
        $dumper1 = $this->createDumper(true);
        $dumper2 = $this->createDumper(false);

        yield 'one dumper when cache is fresh' => [
            'cacheMap' => [
                ['configCacheWarmer' => $this->createWarmer(false), 'configMetadataDumper' => $dumper1]
            ]
        ];

        yield 'one dumper when cache is not fresh' => [
            'cacheMap' => [
                ['configCacheWarmer' => $this->createWarmer(true), 'configMetadataDumper' => $dumper2]
            ]
        ];

        yield 'two dumpers' => [
            'cacheMap' => [
                ['configCacheWarmer' => $this->createWarmer(true), 'configMetadataDumper' => $dumper2],
                ['configCacheWarmer' => $this->createWarmer(false), 'configMetadataDumper' => $dumper1]
            ]
        ];

        yield 'one dumper for two warmers when cache is fresh' => [
            'cacheMap' => [
                ['configCacheWarmer' => $this->createWarmer(false), 'configMetadataDumper' => $dumper1],
                ['configCacheWarmer' => $this->createWarmer(false), 'configMetadataDumper' => $dumper1]
            ]
        ];

        yield 'one dumper for two warmers when cache is not fresh' => [
            'cacheMap' => [
                ['configCacheWarmer' => $this->createWarmer(true), 'configMetadataDumper' => $dumper2],
                ['configCacheWarmer' => $this->createWarmer(true), 'configMetadataDumper' => $dumper2]
            ]
        ];
    }

    public function testWhenIsDebugFalse()
    {
        $listener = new CacheWarmerListener(false);
        $listener->addConfigCacheProvider(
            $this->createWarmer(false),
            $this->createDumper(false)
        );

        $listener->onKernelRequest();
    }

    /**
     * @param bool $isCall
     * @return ConfigCacheWarmerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createWarmer($isCall)
    {
        $warmer = $this->createMock(ConfigCacheWarmerInterface::class);
        $warmer->expects(true === $isCall ? $this->once() : $this->never())
            ->method('warmUpResourceCache');

        return $warmer;
    }

    /**
     * @param bool $isFresh
     * @return ConfigMetadataDumperInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createDumper($isFresh)
    {
        $dumper = $this->createMock(ConfigMetadataDumperInterface::class);
        $dumper->expects($this->any())
            ->method('isFresh')->willReturn($isFresh);

        return $dumper;
    }
}
