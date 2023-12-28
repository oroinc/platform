<?php

namespace Oro\Bundle\AttachmentBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see AttachmentExtensionAwareInterface}.
 */
trait AttachmentExtensionAwareTrait
{
    /** @var AttachmentExtension */
    protected $attachmentExtension;

    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }
}
