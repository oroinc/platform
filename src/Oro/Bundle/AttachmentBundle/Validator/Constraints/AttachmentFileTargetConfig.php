<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the uploaded content of an attachment file is allowed for the attachment target entity.
 */
class AttachmentFileTargetConfig extends Constraint
{
    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
