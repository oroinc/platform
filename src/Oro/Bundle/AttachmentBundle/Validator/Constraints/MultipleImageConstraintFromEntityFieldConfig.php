<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

class MultipleImageConstraintFromEntityFieldConfig extends MultipleFileConstraintFromEntityFieldConfig
{
    /** @var string */
    public $message = 'oro.attachment.max_number_of_files.images';
}
