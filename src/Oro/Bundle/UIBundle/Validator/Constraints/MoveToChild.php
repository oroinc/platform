<?php

namespace Oro\Bundle\UIBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating tree move operations.
 *
 * Ensures that a tree item cannot be moved to one of its own descendants,
 * which would create an invalid circular hierarchy. Applied at the class level
 * to validate the entire tree collection during move operations.
 */
class MoveToChild extends Constraint
{
    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
