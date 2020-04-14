<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProviderDecorator;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProviderInterface;

class ResizedImagePathProviderDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ResizedImagePathProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerResizedImagePathProvider;

    protected function setUp(): void
    {
        $this->innerResizedImagePathProvider = $this->createMock(ResizedImagePathProviderInterface::class);
    }

    /**
     * @dataProvider pathDataProvider
     *
     * @param string $path
     * @param string $skipPrefix
     * @param string $expectedPath
     */
    public function testGetPathforResizedImage(string $path, string $skipPrefix, string $expectedPath): void
    {
        $provider = new ResizedImagePathProviderDecorator($this->innerResizedImagePathProvider, $skipPrefix);

        $this->innerResizedImagePathProvider
            ->method('getPathForResizedImage')
            ->with($entity = new File(), $width = 10, $height = 20)
            ->willReturn($path);

        self::assertEquals(
            $expectedPath,
            $provider->getPathForResizedImage($entity, $width, $height)
        );
    }

    /**
     * @return array
     */
    public function pathDataProvider(): array
    {
        return [
            [
                'path' => '/sample/foo/bar/file.jpg',
                'skipPrefix' => 'sample/prefix',
                'expectedPath' => '/sample/foo/bar/file.jpg',
            ],
            [
                'path' => '/sample/prefix/foo/bar/file.jpg',
                'skipPrefix' => 'sample/prefix',
                'expectedPath' => '/foo/bar/file.jpg',
            ],
            [
                'path' => '/sample/prefix/foo/bar/file.jpg',
                'skipPrefix' => '/sample/prefix/',
                'expectedPath' => '/foo/bar/file.jpg',
            ],
            [
                'path' => 'sample/prefix/foo/bar/file.jpg',
                'skipPrefix' => 'sample/prefix/',
                'expectedPath' => '/foo/bar/file.jpg',
            ],
        ];
    }

    /**
     * @dataProvider pathDataProvider
     *
     * @param string $path
     * @param string $skipPrefix
     * @param string $expectedPath
     */
    public function testGetPathforFilteredImage(string $path, string $skipPrefix, string $expectedPath): void
    {
        $provider = new ResizedImagePathProviderDecorator($this->innerResizedImagePathProvider, $skipPrefix);

        $this->innerResizedImagePathProvider
            ->method('getPathForFilteredImage')
            ->with($entity = new File(), $filter = 'sample-filter')
            ->willReturn($path);

        self::assertEquals(
            $expectedPath,
            $provider->getPathForFilteredImage($entity, $filter)
        );
    }
}
