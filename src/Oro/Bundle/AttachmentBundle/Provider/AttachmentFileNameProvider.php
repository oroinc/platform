<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Get file name for a given file.
 */
class AttachmentFileNameProvider implements FileNameProviderInterface
{
    public function getFileName(File $file): string
    {
        return $file->getFilename();
    }
}
