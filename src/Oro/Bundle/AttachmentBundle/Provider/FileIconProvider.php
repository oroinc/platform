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

    /**
     * @param array $fileIcons
     */
    public function __construct(array $fileIcons)
    {
        $this->fileIcons = $fileIcons;
    }

    /**
     * Get file type icon for the given entity.
     *
     * @param FileExtensionInterface $entity
     *
     * @return string
     */
    public function getExtensionIconClass(FileExtensionInterface $entity): string
    {
        return $this->fileIcons[$entity->getExtension()] ?? $this->fileIcons['default'];
    }

    /**
     * Get all file icons.
     *
     * @return array
     */
    public function getFileIcons(): array
    {
        return $this->fileIcons;
    }
}
