<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Provides title for given file which can be used, for example in title or alt HTML attributes.
 */
interface FileTitleProviderInterface
{
    /**
     * @param File $file
     * @param Localization|null $localization
     *
     * @return string
     */
    public function getTitle(File $file, Localization $localization = null): string;
}
