<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProvider;
use Symfony\Component\Routing\RequestContextAwareInterface;

class ResizedImagePathProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fileUrlProvider;

    /** @var RequestContextAwareInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $requestContextAware;

    /** @var ResizedImagePathProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->fileUrlProvider = $this->createMock(FileUrlProviderInterface::class);
        $this->requestContextAware = $this->createMock(RequestContextAwareInterface::class);

        $this->provider = new ResizedImagePathProvider($this->fileUrlProvider);
    }

    public function testGetPathforResizedImage(): void
    {
        $this->fileUrlProvider
            ->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($entity = new File(), $width = 10, $height = 20)
            ->willReturn($url = 'sample/url');

        self::assertEquals(
            '/sample/url',
            $this->provider->getPathForResizedImage($entity, $width, $height)
        );
    }

    public function testGetPathforFilteredImage(): void
    {
        $this->fileUrlProvider
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($entity = new File(), $filter = 'sample-filter')
            ->willReturn($url = 'sample/url');

        self::assertEquals(
            '/sample/url',
            $this->provider->getPathForFilteredImage($entity, $filter)
        );
    }
}
