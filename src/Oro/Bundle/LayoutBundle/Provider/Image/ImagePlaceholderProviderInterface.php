<?php

namespace Oro\Bundle\LayoutBundle\Provider\Image;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides the path to the image placeholder.
 */
interface ImagePlaceholderProviderInterface
{
    /**
     * @param string $filter
     * @param int $referenceType
     * @return string|null
     */
    public function getPath(string $filter, int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): ?string;
}
