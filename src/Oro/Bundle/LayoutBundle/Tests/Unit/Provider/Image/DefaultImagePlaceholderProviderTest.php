<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider\Image;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Oro\Bundle\LayoutBundle\Provider\Image\DefaultImagePlaceholderProvider;

class DefaultImagePlaceholderProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheManager|\PHPUnit\Framework\MockObject\MockObject */
    private $imagineCacheManager;

    /** @var string */
    private $defaultPath = '/some/default/image.png';

    /** @var DefaultImagePlaceholderProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->imagineCacheManager = $this->createMock(CacheManager::class);

        $this->provider = new DefaultImagePlaceholderProvider($this->imagineCacheManager, $this->defaultPath);
    }

    public function testGetPath(): void
    {
        $expected = '/some/default/filtered_image.png';
        $filter = 'image_filter';

        $this->imagineCacheManager->expects($this->once())
            ->method('getBrowserPath')
            ->with($this->defaultPath, $filter)
            ->willReturn($expected);

        $this->assertEquals($expected, $this->provider->getPath($filter));
    }
}
