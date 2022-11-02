<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Represents a service to get filenames of all files related to a specified File entity.
 * This interface is used to get all possible variants of files for the File entity,
 * e.g. to remove all of them from a storage.
 */
interface FileNamesProviderInterface
{
    /**
     * Gets filenames of all files related to the given File entity.
     *
     * @param File $file
     *
     * @return string[]
     */
    public function getFileNames(File $file): array;
}
