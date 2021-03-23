<?php

namespace Oro\Bundle\LayoutBundle\Provider\Image;

/**
 * Provides the path to the image placeholder.
 */
interface ImagePlaceholderProviderInterface
{
    /**
     * @param string $filter
     * @return string|null
     */
    public function getPath(string $filter): ?string;
}
