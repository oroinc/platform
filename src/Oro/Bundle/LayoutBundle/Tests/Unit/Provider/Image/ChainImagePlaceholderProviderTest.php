<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider\Image;

use Oro\Bundle\LayoutBundle\Provider\Image\ChainImagePlaceholderProvider;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ChainImagePlaceholderProviderTest extends \PHPUnit\Framework\TestCase
{
    private ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject $provider1;

    private ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject $provider2;

    private ChainImagePlaceholderProvider $provider;

    protected function setUp(): void
    {
        $this->provider1 = $this->createMock(ImagePlaceholderProviderInterface::class);
        $this->provider2 = $this->createMock(ImagePlaceholderProviderInterface::class);

        $this->provider = new ChainImagePlaceholderProvider();
        $this->provider->addProvider($this->provider1);
        $this->provider->addProvider($this->provider2);
    }

    public function testGetPathFromFirstProvider(): void
    {
        $data = '/path/to/filtered.img';
        $filter = 'test_filter';
        $format = 'sample_format';

        $this->provider1->expects(self::once())
            ->method('getPath')
            ->with($filter, $format, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($data);

        $this->provider2->expects(self::never())
            ->method(self::anything());

        self::assertEquals($data, $this->provider->getPath($filter, $format));
    }

    public function testGetPathFromSecondProvider(): void
    {
        $data = '/path/to/filtered.img';
        $filter = 'test_filter';
        $format = 'sample_format';

        $this->provider1->expects(self::once())
            ->method('getPath')
            ->with($filter, $format, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn(null);

        $this->provider2->expects(self::once())
            ->method('getPath')
            ->with($filter, $format, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($data);

        self::assertEquals($data, $this->provider->getPath($filter, $format));
    }

    public function testGetPathWithNoData(): void
    {
        $filter = 'test_filter';
        $format = 'sample_format';

        $this->provider1->expects(self::once())
            ->method('getPath')
            ->with($filter, $format, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn(null);

        $this->provider2->expects(self::once())
            ->method('getPath')
            ->with($filter, $format, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn(null);

        self::assertNull($this->provider->getPath($filter, $format));
    }
}
