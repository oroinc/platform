<?php

namespace Oro\Bundle\AttachmentBundle\Resizer;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Tools\ImageFactory;

class ImageResizer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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

    /**
     * @var string
     */
    protected $cacheResolverName;

    /**
     * @param AttachmentManager $attachmentManager
     * @param CacheManager $cacheManager
     * @param FileManager $fileManager
     * @param ImageFactory $imageFactory
     * @param string $cacheResolverName
     */
    public function __construct(
        AttachmentManager $attachmentManager,
        CacheManager $cacheManager,
        FileManager $fileManager,
        ImageFactory $imageFactory,
        $cacheResolverName
    ) {
        $this->attachmentManager = $attachmentManager;
        $this->cacheManager = $cacheManager;
        $this->fileManager = $fileManager;
        $this->imageFactory = $imageFactory;
        $this->cacheResolverName = $cacheResolverName;
    }

    /**
     * @param File $image
     * @param string $filterName
     * @param bool $force
     * @return BinaryInterface|false False if image has been already stored and no force flag passed or on error
     */
    public function resizeImage(File $image, $filterName, $force)
    {
        $path = $this->getPath($image, $filterName);

        return $this->createAndStoreImage($image, $filterName, $path, $force);
    }

    /**
     * @param File $image
     * @param string $filterName
     * @return string
     */
    protected function getPath(File $image, $filterName)
    {
        return $this->attachmentManager->getFilteredImageUrl($image, $filterName);
    }

    /**
     * @param File $image
     * @param string $filterName
     * @param string $path
     * @param bool $force
     * @return BinaryInterface|false
     */
    protected function createAndStoreImage(File $image, $filterName, $path, $force)
    {
        if (!$force && $this->cacheManager->isStored($path, $filterName, $this->cacheResolverName)) {
            return false;
        }

        try {
            $content = $this->fileManager->getContent($image);
        } catch (\Exception $e) {
            if (null !== $this->logger) {
                $this->logger->warning(
                    sprintf(
                        'Image (id: %d, filename: %s) not found. Skipped during resize.',
                        $image->getId(),
                        $image->getFilename()
                    ),
                    ['exception' => $e]
                );
            }

            return false;
        }

        $filteredBinary = $this->imageFactory->createImage($content, $filterName);
        $this->cacheManager->store($filteredBinary, $path, $filterName, $this->cacheResolverName);

        return $filteredBinary;
    }
}
