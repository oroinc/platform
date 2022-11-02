<?php

namespace Oro\Bundle\AttachmentBundle\Manager\FileRemoval;

/**
 * An interface for classes that extracts a directory a file path starts with.
 */
interface DirectoryExtractorInterface
{
    /**
     * Extracts a directory the given file path starts with.
     *
     * @param string $path The path to a file, e.g.: dir1/dir2/file.txt
     *
     * @return string|null
     */
    public function extract(string $path): ?string;

    /**
     * Indicates whether it is allowed to use a delete from a storage operation by the extracted directory
     * even if the directory contains only one file.
     */
    public function isAllowedToUseForSingleFile(): bool;
}
