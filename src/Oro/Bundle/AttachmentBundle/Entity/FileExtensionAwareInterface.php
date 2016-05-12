<?php

namespace Oro\Bundle\AttachmentBundle\Entity;

interface FileExtensionAwareInterface
{
    /**
     * Get file extension
     *
     * @return string
     */
    public function getExtension();
}
