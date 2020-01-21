<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

/**
 * Constraint for checking max number of the uploaded image according to entity field config.
 */
class MultipleImageConstraintFromEntityFieldConfig extends MultipleFileConstraintFromEntityFieldConfig
{
    /** @var string */
    public $message = 'oro.attachment.max_number_of_files.images';
}
