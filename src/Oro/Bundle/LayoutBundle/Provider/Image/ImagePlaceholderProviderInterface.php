<?php

namespace Oro\Bundle\LayoutBundle\Provider\Image;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides the path to the image placeholder.
 */
interface ImagePlaceholderProviderInterface
{
    public function getPath(
        string $filter,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): ?string;
}
