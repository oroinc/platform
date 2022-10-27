<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;

/**
 * Provides sources array that can be used in <picture> tag of an image.
 */
class WebpAwarePictureSourcesProvider implements PictureSourcesProviderInterface
{
    private PictureSourcesProviderInterface $innerPictureSourcesProvider;

    private AttachmentManager $attachmentManager;

    private array $unsupportedMimeTypes;

    public function __construct(
        PictureSourcesProviderInterface $innerPictureSourcesProvider,
        AttachmentManager $attachmentManager,
        array $unsupportedMimeTypes
    ) {
        $this->innerPictureSourcesProvider = $innerPictureSourcesProvider;
        $this->attachmentManager = $attachmentManager;
        $this->unsupportedMimeTypes = $unsupportedMimeTypes;
    }

    /**
     * Adds WebP image variants if current oro_attachment.webp_strategy is "if_supported".
     *
     * {@inheritdoc}
     */
    public function getFilteredPictureSources(?File $file, string $filterName = 'original'): array
    {
        $sources = $this->innerPictureSourcesProvider->getFilteredPictureSources($file, $filterName);

        if ($file instanceof File
            && $file->getExternalUrl() === null
            && $file->getExtension() !== 'webp'
            && $this->attachmentManager->isWebpEnabledIfSupported()
            && !in_array($file->getMimeType(), $this->unsupportedMimeTypes, true)
        ) {
            $sources['sources'][] = [
                'srcset' => $this->attachmentManager->getFilteredImageUrl($file, $filterName, 'webp'),
                'type' => 'image/webp',
            ];
        }

        return $sources;
    }

    /**
     * Adds WebP image variants if current oro_attachment.webp_strategy is "if_supported".
     *
     * {@inheritdoc}
     */
    public function getResizedPictureSources(?File $file, int $width, int $height): array
    {
        $sources = $this->innerPictureSourcesProvider->getResizedPictureSources($file, $width, $height);

        if ($file instanceof File
            && $file->getExternalUrl() === null
            && $file->getExtension() !== 'webp'
            && $this->attachmentManager->isWebpEnabledIfSupported()
            && !in_array($file->getMimeType(), $this->unsupportedMimeTypes, true)
        ) {
            $sources['sources'][] = [
                'srcset' => $this->attachmentManager->getResizedImageUrl($file, $width, $height, 'webp'),
                'type' => 'image/webp',
            ];
        }

        return $sources;
    }
}
