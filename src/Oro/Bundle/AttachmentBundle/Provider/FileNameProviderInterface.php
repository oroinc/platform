<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Represents a service to get a filename for a specified File entity.
 * This interface is used to generate file links.
 */
interface FileNameProviderInterface
{
    /**
     * Gets a filename for the given File entity.
     */
    public function getFileName(File $file): string;
}
