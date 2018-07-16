<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Resizer;

use Imagine\Exception\RuntimeException;
use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Resizer\ImageResizer;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\ImagineBinaryByFileContentFactoryInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\ImagineBinaryFilterInterface;
use Psr\Log\LoggerInterface;

class ImageResizerTest extends \PHPUnit\Framework\TestCase
{
    const FILTER_NAME = 'filter';
    const MIME_TYPE = 'image/gif';
    const FORMAT = 'gif';
    const CONTENT = 'content';

    /**
     * @var ImageResizer
     */
    private $resizer;

    /**
     * @var FileManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fileManager;

    /**
     * @var ImagineBinaryByFileContentFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $imagineBinaryFactory;

    /**
     * @var ImagineBinaryFilterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $imagineBinaryFilter;

    public function setUp()
    {
        $this->imagineBinaryFactory
            = $this->createMock(ImagineBinaryByFileContentFactoryInterface::class);
        $this->imagineBinaryFilter = $this->createMock(ImagineBinaryFilterInterface::class);
        $this->fileManager = $this->createMock(FileManager::class);

        $this->resizer = new ImageResizer(
            $this->fileManager,
            $this->imagineBinaryFactory,
            $this->imagineBinaryFilter
        );
    }

    public function testImageNotFound()
    {
        $exception = new \Exception();

        $logger = $this->createLoggerMock();
        $logger
            ->method('warning')
            ->with(
                'Image (id: 1, filename: image.jpg) not found. Skipped during resize.',
                ['exception' => $exception]
            );
        $this->resizer->setLogger($logger);

        $image = $this->createFileMock();
        $image->method('getId')->willReturn(1);
        $image->method('getFilename')->willReturn('image.jpg');

        $this->fileManager
            ->method('getContent')
            ->with($image)
            ->willThrowException($exception);

        $this->assertFalse($this->resizer->resizeImage($image, self::FILTER_NAME));
    }

    public function testImageIsBroken()
    {
        $exception = new RuntimeException();

        $logger = $this->createLoggerMock();
        $logger
            ->method('warning')
            ->with(
                'Image (id: 1, filename: image.jpg) is broken. Skipped during resize.',
                ['exception' => $exception]
            );
        $this->resizer->setLogger($logger);

        $image = $this->createFileMock();
        $image->method('getId')->willReturn(1);
        $image->method('getFilename')->willReturn('image.jpg');

        $this->fileManager
            ->method('getContent')
            ->with($image)
            ->willReturn(self::CONTENT);
        $binary = $this->createBinaryMock();
        $this->imagineBinaryFactory
            ->method('createImagineBinary')
            ->with(self::CONTENT)
            ->willReturn($binary);

        $this->imagineBinaryFilter
            ->method('applyFilter')
            ->with($binary, self::FILTER_NAME)
            ->willThrowException($exception);

        $this->assertFalse($this->resizer->resizeImage($image, self::FILTER_NAME));
    }

    public function testResizeImage()
    {
        $binary = $this->createBinaryMock();
        $filteredBinary = $this->createBinaryMock();

        $image = $this->createFileMock();
        $image->setMimeType(self::MIME_TYPE);

        $this->fileManager
            ->method('getContent')
            ->with($image)
            ->willReturn(self::CONTENT);

        $this->imagineBinaryFactory
            ->method('createImagineBinary')
            ->with(self::CONTENT)
            ->willReturn($binary);

        $this->imagineBinaryFilter
            ->method('applyFilter')
            ->with($binary, self::FILTER_NAME)
            ->willReturn($filteredBinary);

        $this->assertEquals(
            $filteredBinary,
            $this->resizer->resizeImage($image, self::FILTER_NAME)
        );
    }

    /**
     * @return LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return File|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createFileMock()
    {
        return $this->createMock(File::class);
    }

    /**
     * @return BinaryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createBinaryMock()
    {
        return $this->createMock(BinaryInterface::class);
    }
}
