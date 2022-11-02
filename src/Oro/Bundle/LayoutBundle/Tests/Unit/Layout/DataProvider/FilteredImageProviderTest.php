<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\FilteredImageProvider;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;

class FilteredImageProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttachmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentManager;

    /** @var ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $imagePlaceholderProvider;

    /** @var FilteredImageProvider */
    private $placeholderDataProvider;

    protected function setUp(): void
    {
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);

        $this->placeholderDataProvider = new FilteredImageProvider(
            $this->attachmentManager,
            $this->imagePlaceholderProvider
        );
    }

    public function testGetImageUrl(): void
    {
        $file = new File();
        $filter = 'filter';

        $this->attachmentManager->expects($this->once())
            ->method('getFilteredImageUrl')
            ->with($file, $filter)
            ->willReturn('/path/to/filtered/image');

        $this->imagePlaceholderProvider->expects($this->never())
            ->method('getPath');

        $this->assertEquals(
            '/path/to/filtered/image',
            $this->placeholderDataProvider->getImageUrl($file, $filter)
        );
    }

    /**
     * @dataProvider pathDataProvider
     */
    public function testGetImageUrlWithoutFile(?string $path, string $expectedPath): void
    {
        $this->attachmentManager->expects($this->never())
            ->method('getFilteredImageUrl');

        $this->imagePlaceholderProvider->expects($this->once())
            ->method('getPath')
            ->with('filter')
            ->willReturn($path);

        $this->assertEquals($expectedPath, $this->placeholderDataProvider->getImageUrl(null, 'filter'));
    }

    /**
     * @dataProvider pathDataProvider
     */
    public function testGetPlaceholder(?string $path, string $expectedPath): void
    {
        $this->imagePlaceholderProvider->expects($this->once())
            ->method('getPath')
            ->with('filter')
            ->willReturn($path);

        $this->assertEquals($expectedPath, $this->placeholderDataProvider->getPlaceholder('filter'));
    }

    public function pathDataProvider(): array
    {
        return [
            'string path' => [
                'path' => '/path',
                'expectedPath' => '/path',
            ],
            'null path' => [
                'path' => null,
                'expectedPath' => '',
            ],
        ];
    }
}
