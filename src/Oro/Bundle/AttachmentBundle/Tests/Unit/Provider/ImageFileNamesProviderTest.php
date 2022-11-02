<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\ImageFileNamesProvider;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProviderInterface;

class ImageFileNamesProviderTest extends \PHPUnit\Framework\TestCase
{
    private FilterConfiguration|\PHPUnit\Framework\MockObject\MockObject $filterConfiguration;

    private ResizedImagePathProviderInterface|\PHPUnit\Framework\MockObject\MockObject $imagePathProvider;

    private ImageFileNamesProvider $fileNamesProvider;

    protected function setUp(): void
    {
        $this->filterConfiguration = $this->createMock(FilterConfiguration::class);
        $this->imagePathProvider = $this->createMock(ResizedImagePathProviderInterface::class);

        $this->fileNamesProvider = new ImageFileNamesProvider(
            $this->filterConfiguration,
            $this->imagePathProvider
        );
    }

    public function testGetFileNames(): void
    {
        $file = $this->createMock(File::class);

        $this->filterConfiguration->expects(self::once())
            ->method('all')
            ->willReturn([
                'filter1' => [],
                'filter2' => [],
            ]);
        $this->imagePathProvider->expects(self::exactly(4))
            ->method('getPathForFilteredImage')
            ->withConsecutive(
                [$file, 'filter1'],
                [$file, 'filter1', 'webp'],
                [$file, 'filter2'],
                [$file, 'filter2', 'webp']
            )
            ->willReturnOnConsecutiveCalls(
                '/attachment/filter/filter1/file.jpg',
                '/attachment/filter/filter1/file.jpg.webp',
                '/attachment/filter/filter2/file.jpg',
                '/attachment/filter/filter2/file.jpg.webp'
            );
        $this->imagePathProvider->expects(self::exactly(2))
            ->method('getPathForResizedImage')
            ->withConsecutive(
                [$file, 1, 1],
                [$file, 1, 1, 'webp']
            )
            ->willReturnOnConsecutiveCalls(
                '/attachment/resize/1/1/file.jpg',
                '/attachment/resize/1/1/file.jpg.webp'
            );

        self::assertSame(
            [
                'attachment/filter/filter1/file.jpg',
                'attachment/filter/filter1/file.jpg.webp',
                'attachment/filter/filter2/file.jpg',
                'attachment/filter/filter2/file.jpg.webp',
                'attachment/resize/1/1/file.jpg',
                'attachment/resize/1/1/file.jpg.webp',
            ],
            $this->fileNamesProvider->getFileNames($file)
        );
    }
}
