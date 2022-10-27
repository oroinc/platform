<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Provides title for given file as its original filename.
 */
class FileTitleProvider implements FileTitleProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getTitle(File $file, Localization $localization = null): string
    {
        return (string)$file->getOriginalFilename();
    }
}
