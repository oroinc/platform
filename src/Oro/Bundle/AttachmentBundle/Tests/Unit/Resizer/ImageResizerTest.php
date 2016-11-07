<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Resizer;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Model\Binary;

use Prophecy\Argument;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Resizer\ImageResizer;
use Oro\Bundle\AttachmentBundle\Tools\ImageFactory;
use Psr\Log\LoggerInterface;

class ImageResizerTest extends \PHPUnit_Framework_TestCase
{
    const CACHE_RESOLVER_NAME = 'resolver';
    const FILTER_NAME = 'filter';
    const PATH = 'path';
    const MIME_TYPE = 'image/gif';
    const FORMAT = 'gif';
    const CONTENT = 'content';

    /**
     * @var ImageResizer
     */
    protected $resizer;

    /**
     * @var AttachmentManager
     */
    protected $attachmentManager;

    /**
     * @var CacheManager
     */
    protected $cacheManager;

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
        $this->attachmentManager = $this->prophesize(AttachmentManager::class);
        $this->cacheManager = $this->prophesize(CacheManager::class);
        $this->imageFactory = $this->prophesize(ImageFactory::class);
        $this->fileManager = $this->prophesize(FileManager::class);

        $this->resizer = new ImageResizer(
            $this->attachmentManager->reveal(),
            $this->cacheManager->reveal(),
            $this->fileManager->reveal(),
            $this->imageFactory->reveal(),
            self::CACHE_RESOLVER_NAME
        );
    }

    public function testResizeImageWhenImageExistsAndNoForce()
    {
        $image = $this->prophesize(File::class);
        $image->getId()->willReturn(1);
        $image->getFileName()->willReturn('image.jpg');

        $this->attachmentManager->getFilteredImageUrl($image, self::FILTER_NAME)->willReturn(self::PATH);

        $this->cacheManager->isStored(self::PATH, self::FILTER_NAME, self::CACHE_RESOLVER_NAME)->willReturn(true);
        $this->cacheManager->store(Argument::any())->shouldNotBeCalled();

        $this->assertFalse($this->resizer->resizeImage($image->reveal(), self::FILTER_NAME, false));
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

        $this->attachmentManager->getFilteredImageUrl($image, self::FILTER_NAME)->willReturn(self::PATH);

        $this->fileManager->getContent($image)->willThrow($exception);

        $this->cacheManager->isStored(self::PATH, self::FILTER_NAME, self::CACHE_RESOLVER_NAME)->willReturn(false);
        $this->cacheManager->store(Argument::any())->shouldNotBeCalled();

        $this->assertFalse($this->resizer->resizeImage($image->reveal(), self::FILTER_NAME, false));
    }

    public function testResizeImageWhenImageExistsAndForce()
    {
        $image = $this->prepareImageAndExpectations($isStored = true);

        $this->assertInstanceOf(
            BinaryInterface::class,
            $this->resizer->resizeImage($image, self::FILTER_NAME, $force = true)
        );
    }

    public function testResizeImageWhenImageDoesNotExist()
    {
        $image = $this->prepareImageAndExpectations($isStored = false);

        $this->assertInstanceOf(
            BinaryInterface::class,
            $this->resizer->resizeImage($image, self::FILTER_NAME, $force = false)
        );
    }

    /**
     * @param bool $isStored
     * @return File
     */
    protected function prepareImageAndExpectations($isStored)
    {
        $filteredBinary = new Binary(self::CONTENT, self::MIME_TYPE, self::FORMAT);

        $image = new File();
        $image->setMimeType(self::MIME_TYPE);

        $this->fileManager->getContent($image)->willReturn(self::CONTENT);

        $this->imageFactory->createImage(self::CONTENT, self::FILTER_NAME)->willReturn($filteredBinary);

        $this->attachmentManager->getContent($image)->willReturn(self::CONTENT);
        $this->attachmentManager->getFilteredImageUrl($image, self::FILTER_NAME)->willReturn(self::PATH);

        $this->cacheManager->isStored(self::PATH, self::FILTER_NAME, self::CACHE_RESOLVER_NAME)->willReturn($isStored);
        $this->cacheManager
            ->store($filteredBinary, self::PATH, self::FILTER_NAME, self::CACHE_RESOLVER_NAME)
            ->shouldBeCalled();

        return $image;
    }
}
