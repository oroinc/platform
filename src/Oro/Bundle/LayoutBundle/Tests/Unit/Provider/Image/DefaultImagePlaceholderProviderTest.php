<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider\Image;

use Oro\Bundle\AttachmentBundle\Imagine\Provider\ImagineUrlProviderInterface;
use Oro\Bundle\LayoutBundle\Provider\Image\DefaultImagePlaceholderProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DefaultImagePlaceholderProviderTest extends \PHPUnit\Framework\TestCase
{
    private const DEFAULT_PATH = '/some/default/image.png';

    private ImagineUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject $imagineUrlProvider;

    private DefaultImagePlaceholderProvider $provider;

    protected function setUp(): void
    {
        $this->imagineUrlProvider = $this->createMock(ImagineUrlProviderInterface::class);

        $this->provider = new DefaultImagePlaceholderProvider($this->imagineUrlProvider, self::DEFAULT_PATH);
    }

    public function testGetPath(): void
    {
        $expected = '/some/default/filtered_image.png';
        $filter = 'image_filter';
        $format = 'sample_format';

        $this->imagineUrlProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with(self::DEFAULT_PATH, $filter, $format, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($expected);

        self::assertEquals($expected, $this->provider->getPath($filter, $format));
    }
}
