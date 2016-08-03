<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Resizer;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Model\Binary;

use Prophecy\Argument;

use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesserInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Resizer\ImageResizer;


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
     * @var ExtensionGuesserInterface
     */
    protected $extensionGuesser;

    /**
     * @var FilterManager
     */
    protected $filterManager;

    public function setUp()
    {
        $this->attachmentManager = $this->prophesize(AttachmentManager::class);
        $this->cacheManager = $this->prophesize(CacheManager::class);
        $this->filterManager= $this->prophesize(FilterManager::class);
        $this->extensionGuesser = $this->prophesize(ExtensionGuesserInterface::class);

        $this->resizer = new ImageResizer(
            $this->attachmentManager->reveal(),
            $this->cacheManager->reveal(),
            $this->filterManager->reveal(),
            $this->extensionGuesser->reveal(),
            self::CACHE_RESOLVER_NAME
        );
    }

    public function testResizeImageWhenImageExistsAndNoForce()
    {
        $image = new File();

        $this->attachmentManager->getFilteredImageUrl($image, self::FILTER_NAME)->willReturn(self::PATH);

        $this->cacheManager->isStored(self::PATH, self::FILTER_NAME, self::CACHE_RESOLVER_NAME)->willReturn(true);
        $this->cacheManager->store(Argument::any())->shouldNotBeCalled();

        $this->assertFalse($this->resizer->resizeImage($image, self::FILTER_NAME, false));
    }

    public function testResizeImageWhenImageExistsAndForce()
    {
        $image = $this->prepareImageAndExpectations($isStored = true);

        $this->assertTrue($this->resizer->resizeImage($image, self::FILTER_NAME, $force = true));
    }

    public function testResizeImageWhenImageDoesNotExist()
    {
        $image = $this->prepareImageAndExpectations($isStored = false);

        $this->assertTrue($this->resizer->resizeImage($image, self::FILTER_NAME, $force = false));
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

        $this->filterManager
            ->applyFilter(Argument::type(Binary::class), self::FILTER_NAME)
            ->willReturn($filteredBinary);

        $this->extensionGuesser->guess(self::MIME_TYPE)->willReturn(self::FORMAT);

        $this->attachmentManager->getContent($image)->willReturn(self::CONTENT);
        $this->attachmentManager->getFilteredImageUrl($image, self::FILTER_NAME)->willReturn(self::PATH);

        $this->cacheManager->isStored(self::PATH, self::FILTER_NAME, self::CACHE_RESOLVER_NAME)->willReturn($isStored);
        $this->cacheManager
            ->store($filteredBinary, self::PATH, self::FILTER_NAME, self::CACHE_RESOLVER_NAME)
            ->shouldBeCalled();

        return $image;
    }
}
