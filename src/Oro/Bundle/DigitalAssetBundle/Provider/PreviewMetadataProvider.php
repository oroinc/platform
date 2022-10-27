<?php

namespace Oro\Bundle\DigitalAssetBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileIconProvider;
use Oro\Bundle\AttachmentBundle\Provider\FileTitleProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypeChecker;

/**
 * Provides preview metadata for digital-asset-select-*-grid
 */
class PreviewMetadataProvider implements PreviewMetadataProviderInterface
{
    protected const PREVIEW_FILTER_NAME = 'digital_asset_icon';

    /** @var FileUrlProviderInterface */
    private $fileUrlProvider;

    /** @var MimeTypeChecker */
    private $mimeTypeChecker;

    /** @var FileIconProvider */
    private $fileIconProvider;

    /** @var FileTitleProviderInterface */
    private $fileTitleProvider;

    public function __construct(
        FileUrlProviderInterface $fileUrlProvider,
        MimeTypeChecker $mimeTypeChecker,
        FileIconProvider $fileIconProvider,
        FileTitleProviderInterface $fileTitleProvider
    ) {
        $this->fileUrlProvider = $fileUrlProvider;
        $this->mimeTypeChecker = $mimeTypeChecker;
        $this->fileIconProvider = $fileIconProvider;
        $this->fileTitleProvider = $fileTitleProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(File $file): array
    {
        $previewUrl = '';
        if ($this->mimeTypeChecker->isImageMimeType((string)$file->getMimeType())) {
            $previewUrl = $this->fileUrlProvider
                ->getFilteredImageUrl($file, static::PREVIEW_FILTER_NAME);
        }

        return [
            'filename' => $file->getOriginalFilename(),
            'title' => $this->fileTitleProvider->getTitle($file),
            'preview' => $previewUrl,
            'icon' => $this->fileIconProvider->getExtensionIconClass($file),
            'download' => $this->fileUrlProvider
                ->getFileUrl($file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD),
        ];
    }
}
