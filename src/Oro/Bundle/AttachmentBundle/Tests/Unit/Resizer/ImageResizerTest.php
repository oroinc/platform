<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Resizer;

use Liip\ImagineBundle\Model\Binary;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Resizer\ImageResizer;
use Oro\Bundle\AttachmentBundle\Tools\ImageFactory;
use Psr\Log\LoggerInterface;

class ImageResizerTest extends \PHPUnit_Framework_TestCase
{
    const FILTER_NAME = 'filter';
    const MIME_TYPE = 'image/gif';
    const FORMAT = 'gif';
    const CONTENT = 'content';

    /**
     * @var ImageResizer
     */
    protected $resizer;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var ImageFactory
     */
    protected $imageFactory;

    public function setUp()
    {
        $this->imageFactory = $this->prophesize(ImageFactory::class);
        $this->fileManager = $this->prophesize(FileManager::class);

        $this->resizer = new ImageResizer(
            $this->fileManager->reveal(),
            $this->imageFactory->reveal()
        );
    }

    public function testImageNotFound()
    {
        $exception = new \Exception();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->warning(
                'Image (id: 1, filename: image.jpg) not found. Skipped during resize.',
                ['exception' => $exception]
            )
            ->shouldBeCalled();
        $this->resizer->setLogger($logger->reveal());

        $image = $this->prophesize(File::class);
        $image->getId()->willReturn(1);
        $image->getFilename()->willReturn('image.jpg');

        $this->fileManager->getContent($image)->willThrow($exception);

        $this->assertFalse($this->resizer->resizeImage($image->reveal(), self::FILTER_NAME, false));
    }

    public function testResizeImage()
    {
        $filteredBinary = new Binary(self::CONTENT, self::MIME_TYPE, self::FORMAT);

        $image = new File();
        $image->setMimeType(self::MIME_TYPE);

        $this->fileManager->getContent($image)->willReturn(self::CONTENT);
        $this->imageFactory->createImage(self::CONTENT, self::FILTER_NAME)->willReturn($filteredBinary);

        $this->assertEquals(
            $filteredBinary,
            $this->resizer->resizeImage($image, self::FILTER_NAME)
        );
    }
}
