<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Represents a service to remove all files related to a specified File entity.
 */
interface FileRemovalManagerInterface
{
    /**
     * Removes all files related to the given File entity.
     */
    public function removeFiles(File $file): void;
}
