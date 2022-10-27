<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

/**
 * The form type to select allowed MIME types for images.
 */
class ImageMimeTypeConfigType extends MimeTypeConfigType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_attachment_mime_types_image';
    }
}
