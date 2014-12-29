<?php

namespace Oro\Bundle\AttachmentBundle\Migration\Extension;

/**
 * AttachmentExtensionAwareInterface should be implemented by migrations that depends on a AttachmentExtension.
 */
interface AttachmentExtensionAwareInterface
{
    /**
     * Sets the AttachmentExtension
     *
     * @param AttachmentExtension $attachmentExtension
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension);
}
