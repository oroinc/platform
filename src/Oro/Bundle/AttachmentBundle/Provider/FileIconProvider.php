<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\FileExtensionInterface;

/**
 * Provides file type icon for the given file entity.
 */
class FileIconProvider
{
    /** @var array */
    private $fileIcons;

    public function __construct(array $fileIcons)
    {
        $this->fileIcons = $fileIcons;
    }

    /**
     * Get file type icon for the given entity.
     */
    public function getExtensionIconClass(FileExtensionInterface $entity): string
    {
        return $this->fileIcons[$entity->getExtension()] ?? $this->fileIcons['default'];
    }

    /**
     * Get all file icons.
     */
    public function getFileIcons(): array
    {
        return $this->fileIcons;
    }
}
