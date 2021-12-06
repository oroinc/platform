<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tools\FilenameSanitizer;

/**
 * Returns a filename for a specific File entity as is.
 */
class FileNameProvider implements FileNameProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFileName(File $file, string $format = ''): string
    {
        $extension = $format && $file->getExtension() !== $format ? '.' . $format : '';

        return FilenameSanitizer::sanitizeFilename($file->getFilename() . $extension);
    }
}
