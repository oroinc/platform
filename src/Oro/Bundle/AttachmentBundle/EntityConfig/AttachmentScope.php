<?php

namespace Oro\Bundle\AttachmentBundle\EntityConfig;

class AttachmentScope
{
    const ATTACHMENT_ENTITY = 'Oro\Bundle\AttachmentBundle\Entity\File';

    const ATTACHMENT_FILE   = 'file';
    const ATTACHMENT_IMAGE  = 'image';

    public static $attachmentTypes = ['file', 'image'];
}
