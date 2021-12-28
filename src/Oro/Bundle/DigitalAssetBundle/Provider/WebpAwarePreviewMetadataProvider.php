<?php

namespace Oro\Bundle\DigitalAssetBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;

/**
 * Provides preview metadata for digital-asset-select-*-grid
 */
class WebpAwarePreviewMetadataProvider implements PreviewMetadataProviderInterface
{
    protected const PREVIEW_FILTER_NAME = 'digital_asset_icon';

    private PreviewMetadataProviderInterface $innerPreviewMetadataProvider;

    private AttachmentManager $attachmentManager;

    public function __construct(
        PreviewMetadataProviderInterface $innerPreviewMetadataProvider,
        AttachmentManager $attachmentManager
    ) {
        $this->innerPreviewMetadataProvider = $innerPreviewMetadataProvider;
        $this->attachmentManager = $attachmentManager;
    }

    public function getMetadata(File $file): array
    {
        $metadata = $this->innerPreviewMetadataProvider->getMetadata($file);
        if (!empty($metadata['preview']) && $this->attachmentManager->isWebpEnabledIfSupported()) {
            $metadata['preview_webp'] = $this->attachmentManager
                ->getFilteredImageUrl($file, static::PREVIEW_FILTER_NAME, 'webp');
        }

        return $metadata;
    }
}
