<?php

namespace Oro\Bundle\DigitalAssetBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Interface for digital asset preview metadata providers.
 */
interface PreviewMetadataProviderInterface
{
    public function getMetadata(File $file): array;
}
