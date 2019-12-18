<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Get file name for a given file. This interface is used to generate file links.
 */
interface FileNameProviderInterface
{
    /**
     * @param File $file
     * @return string
     */
    public function getFileName(File $file): string;
}
