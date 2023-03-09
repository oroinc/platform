<?php

namespace Oro\Bundle\AttachmentBundle\Async;

/**
 * Attachment related message queue topics.
 */
class Topics
{
    public const ATTACHMENT_REMOVE_IMAGE = 'oro_attachment.remove_image';
    public const ATTACHMENT_FILES_CLEANUP = 'oro_attachment.cleanup_files';
}
