<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Oro\Bundle\SecurityBundle\Exception\UnsupportedOwnerTreeProviderException;
use Oro\Bundle\SecurityBundle\Owner\ChainOwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;

class ChainOwnerTreeProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testSupportsTrue()
    {
        $provider1 = $this->createMock(OwnerTreeProviderInterface::class);
        $provider1->expects($this->once())
            ->method('supports')
            ->willReturn(true);

        $provider2 = $this->createMock(OwnerTreeProviderInterface::class);
        $provider2->expects($this->never())
            ->method('supports');

        $chainProvider = new ChainOwnerTreeProvider([$provider1, $provider2]);
        $this->assertTrue($chainProvider->supports());
    }

    public function testSupportsFalse()
    {
        $provider = $this->createMock(OwnerTreeProviderInterface::class);
        $provider->expects($this->once())
            ->method('supports')
            ->willReturn(false);

        $chainProvider = new ChainOwnerTreeProvider([$provider]);
        $this->assertFalse($chainProvider->supports());
    }

    public function testGetTree()
    {
        $tree = $this->createMock(OwnerTreeInterface::class);

        $provider1 = $this->createMock(OwnerTreeProviderInterface::class);
        $provider1->expects($this->once())
            ->method('supports')
            ->willReturn(true);
        $provider1->expects($this->once())
            ->method('getTree')
            ->willReturn($tree);

        $provider2 = $this->createMock(OwnerTreeProviderInterface::class);
        $provider2->expects($this->never())
            ->method('supports');
        $provider2->expects($this->never())
            ->method('getTree');

        $chainProvider = new ChainOwnerTreeProvider([$provider1, $provider2]);
        $this->assertSame($tree, $chainProvider->getTree());
    }

    public function testGetTreeFailed()
    {
        $this->expectException(UnsupportedOwnerTreeProviderException::class);
        $this->expectExceptionMessage('Supported provider not found in chain');

        $provider = $this->createMock(OwnerTreeProviderInterface::class);
        $provider->expects($this->once())
            ->method('supports')
            ->willReturn(false);
        $provider->expects($this->never())
            ->method('getTree');

        $chainProvider = new ChainOwnerTreeProvider([$provider]);
        $chainProvider->getTree();
    }

    public function testClearCache()
    {
        $provider1 = $this->createMock(OwnerTreeProviderInterface::class);
        $provider1->expects($this->once())
            ->method('clearCache');

        $provider2 = $this->createMock(OwnerTreeProviderInterface::class);
        $provider2->expects($this->once())
            ->method('clearCache');

        $chainProvider = new ChainOwnerTreeProvider([$provider1, $provider2]);
        $chainProvider->clearCache();
    }

    public function testWarmUpCache()
    {
        $provider1 = $this->createMock(OwnerTreeProviderInterface::class);
        $provider1->expects($this->once())
            ->method('warmUpCache');

        $provider2 = $this->createMock(OwnerTreeProviderInterface::class);
        $provider2->expects($this->once())
            ->method('warmUpCache');

        $chainProvider = new ChainOwnerTreeProvider([$provider1, $provider2]);

        $chainProvider->warmUpCache();
    }
}
