<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

/**
 * The form type to select allowed MIME types for files.
 */
class FileMimeTypeConfigType extends MimeTypeConfigType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_attachment_mime_types_file';
    }
}
