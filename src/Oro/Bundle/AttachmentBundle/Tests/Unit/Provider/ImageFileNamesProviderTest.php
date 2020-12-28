<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\ImageFileNamesProvider;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProviderInterface;

class ImageFileNamesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var FilterConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    private $filterConfiguration;

    /** @var ResizedImagePathProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $imagePathProvider;

    /** @var ImageFileNamesProvider */
    private $fileNamesProvider;

    protected function setUp(): void
    {
        $this->filterConfiguration = $this->createMock(FilterConfiguration::class);
        $this->imagePathProvider = $this->createMock(ResizedImagePathProviderInterface::class);

        $this->fileNamesProvider = new ImageFileNamesProvider(
            $this->filterConfiguration,
            $this->imagePathProvider
        );
    }

    public function testGetFileNames()
    {
        $file = $this->createMock(File::class);

        $this->filterConfiguration->expects(self::once())
            ->method('all')
            ->willReturn([
                'filter1' => [],
                'filter2' => []
            ]);
        $this->imagePathProvider->expects(self::exactly(2))
            ->method('getPathForFilteredImage')
            ->withConsecutive(
                [$file, 'filter1'],
                [$file, 'filter2']
            )
            ->willReturnOnConsecutiveCalls(
                '/attachment/filter/filter1/file.jpg',
                '/attachment/filter/filter2/file.jpg'
            );
        $this->imagePathProvider->expects(self::once())
            ->method('getPathForResizedImage')
            ->with($file, 1, 1)
            ->willReturn('/attachment/resize/1/1/file.jpg');

        self::assertSame(
            [
                'attachment/filter/filter1/file.jpg',
                'attachment/filter/filter2/file.jpg',
                'attachment/resize/1/1/file.jpg'
            ],
            $this->fileNamesProvider->getFileNames($file)
        );
    }
}
