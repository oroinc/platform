<?php

namespace Oro\Bundle\AttachmentBundle\Entity;

/**
 * Defines the contract for entities that provide file extension information.
 *
 * This interface should be implemented by entities that need to expose their file extension.
 * It is commonly used for file-related entities to provide a standardized way to retrieve
 * the file extension, which is essential for file type validation, filtering, and display purposes.
 */
interface FileExtensionInterface
{
    /**
     * Get file extension
     *
     * @return string
     */
    public function getExtension();
}
