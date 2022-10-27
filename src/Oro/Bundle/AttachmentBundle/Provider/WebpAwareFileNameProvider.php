<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;

/**
 * Sets default format to webp if webp strategy is enabled for all.
 */
class WebpAwareFileNameProvider implements FileNameProviderInterface
{
    private FileNameProviderInterface $innerFileNameProvider;

    private WebpConfiguration $webpConfiguration;

    private FilterConfiguration $filterConfiguration;

    public function __construct(
        FileNameProviderInterface $innerFileNameProvider,
        WebpConfiguration $webpConfiguration,
        FilterConfiguration $filterConfiguration
    ) {
        $this->innerFileNameProvider = $innerFileNameProvider;
        $this->webpConfiguration = $webpConfiguration;
        $this->filterConfiguration = $filterConfiguration;
    }

    public function getFileName(File $file): string
    {
        return $this->innerFileNameProvider->getFileName($file);
    }

    public function getFilteredImageName(File $file, string $filterName, string $format = ''): string
    {
        if (!$format && $this->webpConfiguration->isEnabledForAll()) {
            $filterFormat = $this->filterConfiguration->get($filterName)['format'] ?? '';
            if (!$filterFormat) {
                $format = 'webp';
            }
        }

        return $this->innerFileNameProvider->getFilteredImageName($file, $filterName, $format);
    }

    public function getResizedImageName(File $file, int $width, int $height, string $format = ''): string
    {
        if (!$format && $this->webpConfiguration->isEnabledForAll()) {
            $format = 'webp';
        }

        return $this->innerFileNameProvider->getResizedImageName($file, $width, $height, $format);
    }
}
