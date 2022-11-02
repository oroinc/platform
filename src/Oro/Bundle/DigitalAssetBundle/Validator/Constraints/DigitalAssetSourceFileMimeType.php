<?php

namespace Oro\Bundle\DigitalAssetBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for checking if MIME type of source file cannot be changed to non-image if digital asset is already
 * used in image fields.
 */
class DigitalAssetSourceFileMimeType extends Constraint
{
    public $mimeTypeCannotBeNonImage = 'oro.digitalasset.validator.mime_type_cannot_be_non_image.message';
    public $mimeTypeCannotBeNonImageInEntity
        = 'oro.digitalasset.validator.mime_type_cannot_be_non_image_in_entity.message';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
