<?php

namespace Oro\Bundle\AttachmentBundle\Entity;

interface FileExtensionInterface
{
    /**
     * Get file extension
     *
     * @return string
     */
    public function getExtension();
}
