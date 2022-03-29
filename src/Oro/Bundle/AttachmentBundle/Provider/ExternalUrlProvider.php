<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides external URL of the file that is stored externally on a third party service.
 */
class ExternalUrlProvider implements FileUrlProviderInterface
{
    private FileUrlProviderInterface $innerFileUrlProvider;

    public function __construct(
        FileUrlProviderInterface $innerFileUrlProvider
    ) {
        $this->innerFileUrlProvider = $innerFileUrlProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileUrl(
        File $file,
        string $action = self::FILE_ACTION_GET,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $file->getExternalUrl() ?? $this->innerFileUrlProvider->getFileUrl($file, $action, $referenceType);
    }

    /**
     * {@inheritdoc}
     */
    public function getResizedImageUrl(
        File $file,
        int $width,
        int $height,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $file->getExternalUrl() ?? $this->innerFileUrlProvider
                ->getResizedImageUrl($file, $width, $height, $format, $referenceType);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilteredImageUrl(
        File $file,
        string $filterName,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $file->getExternalUrl() ?? $this->innerFileUrlProvider
                ->getFilteredImageUrl($file, $filterName, $format, $referenceType);
    }
}
