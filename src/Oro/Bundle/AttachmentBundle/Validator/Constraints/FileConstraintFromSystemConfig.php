<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\File as SymfonyFileConstraint;

/**
 * Constraint for checking mime type and file size of the uploaded file according to system config.
 */
class FileConstraintFromSystemConfig extends SymfonyFileConstraint
{
    public string $maxSizeConfigPath = 'oro_attachment.maxsize';

    public $mimeTypesMessage = 'oro.attachment.mimetypes.invalid_mime_type';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
