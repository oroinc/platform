<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;

/**
 * Additionally converts image to WebP format if needed.
 */
class WebpAwareImageResizeManager implements ImageResizeManagerInterface
{
    private ImageResizeManagerInterface $innerImageResizeManager;

    private WebpConfiguration $webpConfiguration;

    public function __construct(
        ImageResizeManagerInterface $innerImageResizeManager,
        WebpConfiguration $webpConfiguration
    ) {
        $this->innerImageResizeManager = $innerImageResizeManager;
        $this->webpConfiguration = $webpConfiguration;
    }

    public function resize(
        File $file,
        int $width,
        int $height,
        string $format = '',
        bool $forceUpdate = false
    ): ?BinaryInterface {
        if (!$format && $this->webpConfiguration->isEnabledIfSupported()) {
            $this->innerImageResizeManager->resize($file, $width, $height, 'webp', $forceUpdate);
        }

        return $this->innerImageResizeManager->resize($file, $width, $height, $format, $forceUpdate);
    }

    public function applyFilter(
        File $file,
        string $filterName,
        string $format = '',
        bool $forceUpdate = false
    ): ?BinaryInterface {
        if (!$format && $this->webpConfiguration->isEnabledIfSupported()) {
            $this->innerImageResizeManager->applyFilter($file, $filterName, 'webp', $forceUpdate);
        }

        return $this->innerImageResizeManager->applyFilter($file, $filterName, $format, $forceUpdate);
    }
}
