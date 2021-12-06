<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Interface for classes which can provide download/preview URLs for a file.
 */
interface FileUrlProviderInterface
{
    public const FILE_ACTION_GET = 'get';
    public const FILE_ACTION_DOWNLOAD = 'download';

    /**
     * Get file URL.
     */
    public function getFileUrl(
        File $file,
        string $action = self::FILE_ACTION_GET,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string;

    /**
     * Get resized image URL.
     *
     * @param File $file
     * @param int $width
     * @param int $height
     * @param string $format Adds extension to the filename in url. Leave empty to stay with default format.
     * @param int $referenceType
     *
     * @return string
     */
    public function getResizedImageUrl(
        File $file,
        int $width,
        int $height,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string;

    /**
     * Get URL to the image with applied LiipImagine filter.
     *
     * @param File $file
     * @param string $filterName
     * @param string $format Adds extension to the filename in url. Leave empty to stay with default format.
     * @param int $referenceType
     *
     * @return string
     */
    public function getFilteredImageUrl(
        File $file,
        string $filterName,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string;
}
