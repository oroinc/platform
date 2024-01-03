<?php

namespace Oro\Bundle\AttachmentBundle\Migration\Extension;

/**
 * This interface should be implemented by migrations that depend on {@see AttachmentExtension}.
 */
interface AttachmentExtensionAwareInterface
{
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension);
}
