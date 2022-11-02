<?php

namespace Oro\Bundle\AttachmentBundle\Imagine\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Sets default format to webp if webp strategy is enabled for all.
 */
class WebpAwareImagineUrlProvider implements ImagineUrlProviderInterface
{
    private ImagineUrlProviderInterface $innerImagineUrlProvider;

    private FilterConfiguration $filterConfiguration;

    private WebpConfiguration $webpConfiguration;

    public function __construct(
        ImagineUrlProviderInterface $innerImagineUrlProvider,
        FilterConfiguration $filterConfiguration,
        WebpConfiguration $webpConfiguration
    ) {
        $this->innerImagineUrlProvider = $innerImagineUrlProvider;
        $this->filterConfiguration = $filterConfiguration;
        $this->webpConfiguration = $webpConfiguration;
    }

    public function getFilteredImageUrl(
        string $path,
        string $filterName,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ): string {
        if (!$format && $this->webpConfiguration->isEnabledForAll()) {
            $filterFormat = $this->filterConfiguration->get($filterName)['format'] ?? '';
            if (!$filterFormat) {
                $format = 'webp';
            }
        }

        return $this->innerImagineUrlProvider->getFilteredImageUrl($path, $filterName, $format, $referenceType);
    }
}
