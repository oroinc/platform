<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Remove image file and it's resized/filtered variants.
 */
interface ImageRemovalManagerInterface
{
    /**
     * @param File $file
     */
    public function removeImageWithVariants(File $file);

    /**
     * @param array $configuration
     */
    public function setConfiguration(array $configuration);
}
