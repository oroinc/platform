<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider\Image;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Oro\Bundle\LayoutBundle\Provider\Image\DefaultImagePlaceholderProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DefaultImagePlaceholderProviderTest extends \PHPUnit\Framework\TestCase
{
    private const DEFAULT_PATH = '/some/default/image.png';

    /** @var CacheManager|\PHPUnit\Framework\MockObject\MockObject */
    private CacheManager $imagineCacheManager;

    private DefaultImagePlaceholderProvider $provider;

    protected function setUp(): void
    {
        $this->imagineCacheManager = $this->createMock(CacheManager::class);

        $this->provider = new DefaultImagePlaceholderProvider($this->imagineCacheManager, self::DEFAULT_PATH);
    }

    public function testGetPath(): void
    {
        $expected = '/some/default/filtered_image.png';
        $filter = 'image_filter';

        $this->imagineCacheManager->expects(self::once())
            ->method('generateUrl')
            ->with(self::DEFAULT_PATH, $filter, [], null, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($expected);

        $this->assertEquals($expected, $this->provider->getPath($filter));
    }
}
