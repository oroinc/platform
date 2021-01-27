<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Returns a filename for a specific File entity as is.
 */
class FileNameProvider implements FileNameProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getFileName(File $file): string
    {
        return $file->getFilename();
    }
}
