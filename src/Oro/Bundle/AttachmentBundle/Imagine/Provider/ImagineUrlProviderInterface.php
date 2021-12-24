<?php

namespace Oro\Bundle\AttachmentBundle\Imagine\Provider;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Interface for class that generates URL for image with applied LiipImagine filter.
 */
interface ImagineUrlProviderInterface
{
    /**
     * @param string $path Path to the image.
     * @param string $filterName
     * @param string $format Adds extension to the filename in url. Leave empty to stay with default format.
     * @param int $referenceType
     * @return string
     */
    public function getFilteredImageUrl(
        string $path,
        string $filterName,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ): string;
}
