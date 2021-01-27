<?php

namespace Oro\Bundle\AttachmentBundle\Manager\FileRemoval;

/**
 * Represents a configuration for a service to remove all files related to a specified File entity.
 */
interface FileRemovalManagerConfigInterface
{
    /**
     * Gets a configuration for a service to remove all files related to the given File entity.
     * This configuration is used to optimize the number of delete operation for a file storage.
     *
     * @return DirectoryExtractorInterface[] [name => directory matcher, ...]
     */
    public function getConfiguration(): array;
}
