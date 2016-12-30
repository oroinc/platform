<?php

namespace Oro\Bundle\AttachmentBundle\Migration\Extension;

trait AttachmentExtensionAwareTrait
{
    /** @var AttachmentExtension */
    protected $attachmentExtension;

    /**
     * {@inheritdoc}
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }
}
