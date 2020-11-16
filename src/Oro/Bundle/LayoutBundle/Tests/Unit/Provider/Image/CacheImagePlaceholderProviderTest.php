<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider\Image;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\LayoutBundle\Provider\Image\CacheImagePlaceholderProvider;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;

class CacheImagePlaceholderProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $imagePlaceholderProvider;

    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var CacheImagePlaceholderProvider */
    private $decorator;

    protected function setUp(): void
    {
        $this->imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);
        $this->cache = $this->createMock(Cache::class);

        $this->decorator = new CacheImagePlaceholderProvider($this->imagePlaceholderProvider, $this->cache);
    }

    public function testGetPath(): void
    {
        $filter = 'test_filter';
        $path = 'test/path';

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($filter)
            ->willReturn($path);

        $this->cache->expects($this->never())
            ->method('save');

        $this->imagePlaceholderProvider->expects($this->never())
            ->method($this->anything());

        $this->assertEquals($path, $this->decorator->getPath($filter));
    }

    public function testGetPathWhenEmptyCache(): void
    {
        $filter = 'test_filter';
        $path = 'test/path';

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($filter)
            ->willReturn(false);

        $this->cache->expects($this->once())
            ->method('save')
            ->with($filter, $path);

        $this->imagePlaceholderProvider->expects($this->once())
            ->method('getPath')
            ->with($filter)
            ->willReturn($path);

        $this->assertEquals($path, $this->decorator->getPath($filter));
    }
}
