<?php

namespace Oro\Bundle\LayoutBundle\Provider\Image;

/**
 * Provides the path to the image placeholder.
 */
interface ImagePlaceholderProviderInterface
{
    public function getPath(string $filter): ?string;
}
