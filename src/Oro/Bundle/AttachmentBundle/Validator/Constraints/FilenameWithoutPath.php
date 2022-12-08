<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint can be used to check that a string is a file name without a path.
 */
class FilenameWithoutPath extends Constraint
{
    public string $message = 'oro.attachment.filename_with_path.not_allowed';
}
