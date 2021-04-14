<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider\Image;

use Oro\Bundle\LayoutBundle\Provider\Image\ChainImagePlaceholderProvider;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ChainImagePlaceholderProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider1;

    /** @var ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider2;

    /** @var ChainImagePlaceholderProvider */
    private $provider;

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

        $this->provider1->expects($this->once())
            ->method('getPath')
            ->with($filter, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($data);

        $this->provider2->expects($this->never())
            ->method($this->anything());

        $this->assertEquals($data, $this->provider->getPath($filter));
    }

    public function testGetPathFromSecondProvider(): void
    {
        $data = '/path/to/filtered.img';
        $filter = 'test_filter';

        $this->provider1->expects($this->once())
            ->method('getPath')
            ->with($filter, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn(null);

        $this->provider2->expects($this->once())
            ->method('getPath')
            ->with($filter, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($data);

        $this->assertEquals($data, $this->provider->getPath($filter));
    }

    public function testGetPathWithNoData(): void
    {
        $filter = 'test_filter';

        $this->provider1->expects($this->once())
            ->method('getPath')
            ->with($filter, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn(null);

        $this->provider2->expects($this->once())
            ->method('getPath')
            ->with($filter, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn(null);

        $this->assertNull($this->provider->getPath($filter));
    }
}
