<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider\Image;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\LayoutBundle\Provider\Image\CacheImagePlaceholderProvider;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CacheImagePlaceholderProviderTest extends \PHPUnit\Framework\TestCase
{
    private ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject $imagePlaceholderProvider;

    private Cache|\PHPUnit\Framework\MockObject\MockObject $cache;

    private CacheImagePlaceholderProvider $decorator;

    protected function setUp(): void
    {
        $this->imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);
        $this->cache = $this->createMock(Cache::class);

        $this->decorator = new CacheImagePlaceholderProvider($this->imagePlaceholderProvider, $this->cache);
    }

    public function testGetPath(): void
    {
        $filter = 'test_filter';
        $format = 'sample_format';
        $path = 'test/path';
        $cacheKey = $filter . '|' . $format . '|' . UrlGeneratorInterface::ABSOLUTE_PATH;

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn($path);

        $this->cache->expects(self::never())
            ->method('save');

        $this->imagePlaceholderProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals($path, $this->decorator->getPath($filter, $format));
    }

    public function testGetPathWhenEmptyCache(): void
    {
        $filter = 'test_filter';
        $format = 'sample_format';
        $path = 'test/path';
        $cacheKey = $filter . '|' . $format . '|' . UrlGeneratorInterface::ABSOLUTE_PATH;

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn(false);

        $this->cache->expects(self::once())
            ->method('save')
            ->with($cacheKey, $path);

        $this->imagePlaceholderProvider->expects(self::once())
            ->method('getPath')
            ->with($filter, $format, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($path);

        self::assertEquals($path, $this->decorator->getPath($filter, $format));
    }
}
