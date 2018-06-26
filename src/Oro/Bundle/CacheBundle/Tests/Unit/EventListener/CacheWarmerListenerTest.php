<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\EventListener;

use Oro\Bundle\CacheBundle\EventListener\CacheWarmerListener;
use Oro\Bundle\CacheBundle\Provider\ConfigCacheWarmerInterface;
use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;

class CacheWarmerListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheWarmerListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->listener = new CacheWarmerListener();
    }

    /**
     * @dataProvider cacheMapProvider
     *
     * @param array $cacheMap
     */
    public function testOnConsoleCommand(array $cacheMap)
    {
        foreach ($cacheMap as $item) {
            $this->listener->addConfigCacheProvider($item['configCacheWarmer'], $item['configMetadataDumper']);
        }

        $this->listener->onConsoleCommand();
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

    /**
     * @param bool $isCall
     * @return ConfigCacheWarmerInterface|\PHPUnit\Framework\MockObject\MockObject
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
     * @return ConfigMetadataDumperInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createDumper($isFresh)
    {
        $dumper = $this->createMock(ConfigMetadataDumperInterface::class);
        $dumper->expects($this->any())
            ->method('isFresh')->willReturn($isFresh);

        return $dumper;
    }
}
