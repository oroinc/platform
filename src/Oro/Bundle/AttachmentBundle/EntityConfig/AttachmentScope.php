<?php

namespace Oro\Bundle\AttachmentBundle\EntityConfig;

class AttachmentScope
{
    const ATTACHMENT_ENTITY = 'Oro\Bundle\AttachmentBundle\Entity\Attachment';

    const ATTACHMENT_FILE   = 'attachment';
    const ATTACHMENT_IMAGE  = 'attachmentImage';

    public static $attachmentTypes = ['attachment', 'attachmentImage'];
}
