<?php

namespace Oro\Bundle\LayoutBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;

/**
 * Layout data provider that provides path to the image placeholder.
 */
class ImagePlaceholderProvider
{
    /** @var ImagePlaceholderProviderInterface */
    private $imagePlaceholderProvider;

    /**
     * @param ImagePlaceholderProviderInterface $imagePlaceholderProvider
     */
    public function __construct(ImagePlaceholderProviderInterface $imagePlaceholderProvider)
    {
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
    }

    /**
     * @param string $filter
     * @return string
     */
    public function getPath(string $filter): string
    {
        return (string) $this->imagePlaceholderProvider->getPath($filter);
    }
}
