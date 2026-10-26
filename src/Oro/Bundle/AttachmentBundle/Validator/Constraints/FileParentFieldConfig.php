<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the uploaded content of a file is allowed for the parent entity specified in the file.
 */
class FileParentFieldConfig extends Constraint
{
    #[\Override]
    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
