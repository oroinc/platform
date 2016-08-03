<?php

namespace Oro\Bundle\AttachmentBundle\Resizer;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Model\Binary;

use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesserInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;

class ImageResizer
{
    /**
     * @var AttachmentManager
     */
    private $attachmentManager;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var ExtensionGuesserInterface
     */
    private $extensionGuesser;

    /**
     * @var FilterManager
     */
    private $filterManager;

    /**
     * @var string
     */
    private $cacheResolverName;

    /**
     * @param AttachmentManager $attachmentManager
     * @param CacheManager $cacheManager
     * @param FilterManager $filterManager
     * @param ExtensionGuesserInterface $extensionGuesser
     * @param string $cacheResolverName
     */
    public function __construct(
        AttachmentManager $attachmentManager,
        CacheManager $cacheManager,
        FilterManager $filterManager,
        ExtensionGuesserInterface $extensionGuesser,
        $cacheResolverName
    ) {
        $this->attachmentManager = $attachmentManager;
        $this->cacheManager = $cacheManager;
        $this->extensionGuesser = $extensionGuesser;
        $this->cacheResolverName = $cacheResolverName;
        $this->filterManager = $filterManager;
    }

    /**
     * @param File $image
     * @param string $filterName
     * @param bool $force
     * @return bool False if image has been already stored and no force flag passed, true otherwise
     */
    public function resizeImage(File $image, $filterName, $force)
    {
        $path = $this->attachmentManager->getFilteredImageUrl($image, $filterName);

        if (!$force && $this->cacheManager->isStored($path, $filterName, $this->cacheResolverName)) {
            return false;
        }

        $mimeType = $image->getMimeType();
        $format = $this->extensionGuesser->guess($mimeType);
        $content = $this->attachmentManager->getContent($image);

        $binary = new Binary($content, $mimeType, $format);
        $filteredBinary = $this->filterManager->applyFilter($binary, $filterName);

        $this->cacheManager->store($filteredBinary, $path, $filterName, $this->cacheResolverName);

        return true;
    }
}
