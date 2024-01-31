<?php

namespace Oro\Bundle\AttachmentBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see AttachmentExtensionAwareInterface}.
 */
trait AttachmentExtensionAwareTrait
{
    private AttachmentExtension $attachmentExtension;

    public function setAttachmentExtension(AttachmentExtension $attachmentExtension): void
    {
        $this->attachmentExtension = $attachmentExtension;
    }
}
