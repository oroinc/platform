<?php

namespace Oro\Bundle\LayoutBundle\Provider\Image;

use Oro\Bundle\AttachmentBundle\Imagine\Provider\ImagineUrlProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides the path to the default image placeholder.
 */
class DefaultImagePlaceholderProvider implements ImagePlaceholderProviderInterface
{
    private ImagineUrlProviderInterface $imagineUrlProvider;

    private string $defaultPath;

    public function __construct(ImagineUrlProviderInterface $imagineUrlProvider, string $defaultPath)
    {
        $this->imagineUrlProvider = $imagineUrlProvider;
        $this->defaultPath = $defaultPath;
    }

    public function getPath(
        string $filter,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): ?string {
        return $this->imagineUrlProvider->getFilteredImageUrl($this->defaultPath, $filter, $format, $referenceType);
    }
}
