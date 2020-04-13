<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\File;

/**
 * Constraint for checking mime type and file size of the uploaded file according to system config.
 */
class FileConstraintFromSystemConfig extends File
{
    /** @var string */
    public $maxSizeConfigPath = 'oro_attachment.maxsize';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
