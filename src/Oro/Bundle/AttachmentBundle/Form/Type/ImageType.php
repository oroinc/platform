<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

/**
 * Form type for handling image file uploads and configuration.
 *
 * This form type extends the {@see FileType} to provide specialized handling for image files.
 * It inherits all file upload functionality from FileType while maintaining a distinct
 * form type identifier for image-specific form configurations and validation rules.
 */
class ImageType extends AbstractType
{
    const NAME = 'oro_image';

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return FileType::class;
    }
}
