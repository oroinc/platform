<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the external file of a file is allowed for the parent entity field specified in the file.
 */
class ExternalFileParentFieldConfig extends Constraint
{
    #[\Override]
    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
