<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\SyncBundle\Cache\PubSubRouterCacheDecorator;

class PubSubRouterCacheDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    private $innerCache;

    protected function setUp(): void
    {
        $this->innerCache = $this->createMock(Cache::class);
    }

    public function testFetchWhenDebug(): void
    {
        $this->assertFalse($this->getCacheDecorator(true)->fetch('sample_key'));
    }

    public function testContainsWhenDebug(): void
    {
        $this->assertFalse($this->getCacheDecorator(true)->contains('sample_key'));
    }

    public function testSaveWhenDebug(): void
    {
        $this->assertTrue($this->getCacheDecorator(true)->save('sample_key', []));
    }

    public function testFetchWhenNotDebug(): void
    {
        $key = 'sample_key';
        $data = ['sample_data'];
        $this->innerCache
            ->expects($this->once())
            ->method('fetch')
            ->with($key)
            ->willReturn($data);

        $this->assertEquals($data, $this->getCacheDecorator(false)->fetch($key));
    }

    public function testContainsWhenNotDebug(): void
    {
        $key = 'sample_key';
        $this->innerCache
            ->expects($this->once())
            ->method('contains')
            ->with($key)
            ->willReturn(true);

        $this->assertTrue($this->getCacheDecorator(false)->contains($key));
    }

    public function testSaveWhenNotDebug(): void
    {
        $key = 'sample_key';
        $data = ['sample_data'];
        $this->innerCache
            ->expects($this->once())
            ->method('save')
            ->with($key, $data)
            ->willReturn(true);

        $this->assertTrue($this->getCacheDecorator(false)->save('sample_key', $data));
    }

    public function testDelete(): void
    {
        $key = 'sample_key';
        $this->innerCache
            ->expects($this->once())
            ->method('delete')
            ->with($key)
            ->willReturn(true);

        $this->assertTrue($this->getCacheDecorator(false)->delete($key));
    }

    public function testStats(): void
    {
        $stats = ['sample_key'];
        $this->innerCache
            ->expects($this->once())
            ->method('getStats')
            ->willReturn($stats);

        $this->assertEquals($stats, $this->getCacheDecorator(false)->getStats());
    }

    /**
     * @param bool $isDebug
     *
     * @return PubSubRouterCacheDecorator
     */
    private function getCacheDecorator(bool $isDebug): PubSubRouterCacheDecorator
    {
        return new PubSubRouterCacheDecorator($this->innerCache, $isDebug);
    }
}
