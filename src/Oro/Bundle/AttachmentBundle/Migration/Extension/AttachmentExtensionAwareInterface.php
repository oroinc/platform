<?php

namespace Oro\Bundle\AttachmentBundle\Migration\Extension;

/**
 * AttachmentExtensionAwareInterface should be implemented by migrations that depends on a AttachmentExtension.
 */
interface AttachmentExtensionAwareInterface
{
    /**
     * Sets the AttachmentExtension
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension);
}
