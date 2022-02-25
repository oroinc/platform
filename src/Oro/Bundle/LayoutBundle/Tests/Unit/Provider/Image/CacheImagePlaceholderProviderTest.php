<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider\Image;

use Oro\Bundle\LayoutBundle\Provider\Image\CacheImagePlaceholderProvider;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;

class CacheImagePlaceholderProviderTest extends \PHPUnit\Framework\TestCase
{
    private ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject $imagePlaceholderProvider;

    private CacheInterface|\PHPUnit\Framework\MockObject\MockObject $cache;

    private CacheImagePlaceholderProvider $decorator;

    protected function setUp(): void
    {
        $this->imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->decorator = new CacheImagePlaceholderProvider($this->imagePlaceholderProvider, $this->cache);
    }

    public function testGetPath(): void
    {
        $filter = 'test_filter';
        $format = 'sample_format';
        $path = 'test/path';
        $cacheKey = $filter . '|' . $format . '|' . UrlGeneratorInterface::ABSOLUTE_PATH;

        $this->cache->expects(self::once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($path);

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

        $this->imagePlaceholderProvider->expects(self::once())
            ->method('getPath')
            ->with($filter, $format, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($path);
        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturnCallback(function () use ($filter, $format) {
                return $this->imagePlaceholderProvider->getPath(
                    $filter,
                    $format,
                    UrlGeneratorInterface::ABSOLUTE_PATH
                );
            });

        self::assertEquals($path, $this->decorator->getPath($filter, $format));
    }
}
