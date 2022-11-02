<?php

namespace Oro\Bundle\AttachmentBundle\Manager\FileRemoval;

/**
 * Provides a configuration for a service to remove resized/filtered image files related to a specified File entity.
 */
final class ImageFileRemovalManagerConfig implements FileRemovalManagerConfigInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfiguration(): array
    {
        return [
            // attachment/filter/{filter}/{filterMd5}/{id}/{filename}
            'filter' => new DirectoryExtractor('/^(attachment\/filter\/\w+\/\w+\/\d+)\/\w+/', false),
            // attachment/resize/{id}/{width}/{height}/{filename}
            'resize' => new DirectoryExtractor('/^(attachment\/resize\/\d+)\/\d+\/\d+\/\w+/', true)
        ];
    }
}
