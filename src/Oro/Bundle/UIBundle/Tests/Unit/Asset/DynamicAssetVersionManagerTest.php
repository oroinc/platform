<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Asset;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;

class DynamicAssetVersionManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var DynamicAssetVersionManager */
    private $assetVersionManager;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheProvider::class);

        $this->assetVersionManager = new DynamicAssetVersionManager($this->cache);
    }

    public function testGetAssetVersionWithEmptyCache()
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('test')
            ->willReturn(false);

        $this->assertSame('', $this->assetVersionManager->getAssetVersion('test'));
        // test local cache
        $this->assertSame('', $this->assetVersionManager->getAssetVersion('test'));
    }

    public function testGetAssetVersion()
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('test')
            ->willReturn(123);

        $this->assertSame('123', $this->assetVersionManager->getAssetVersion('test'));
        // test local cache
        $this->assertSame('123', $this->assetVersionManager->getAssetVersion('test'));
    }

    public function testUpdateAssetVersionWithEmptyCache()
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('test')
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('save')
            ->with('test')
            ->willReturn(1);

        $this->assetVersionManager->updateAssetVersion('test');
        $this->assertSame('1', $this->assetVersionManager->getAssetVersion('test'));
    }

    public function testUpdateAssetVersion()
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('test')
            ->willReturn(123);
        $this->cache->expects($this->once())
            ->method('save')
            ->with('test')
            ->willReturn(124);

        $this->assetVersionManager->updateAssetVersion('test');
        $this->assertSame('124', $this->assetVersionManager->getAssetVersion('test'));
    }
}
