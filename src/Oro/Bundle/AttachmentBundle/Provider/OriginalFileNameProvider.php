<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Uses a sanitized original filename for files if `attachment_original_filenames` is enabled.
 */
class OriginalFileNameProvider extends AbstractHumanReadableFileNameProvider
{
    protected function isApplicable(File $file): bool
    {
        return $file->getOriginalFilename() && $this->isFeaturesEnabled();
    }
}
