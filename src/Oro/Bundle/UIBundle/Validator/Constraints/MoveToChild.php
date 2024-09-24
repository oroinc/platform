<?php

namespace Oro\Bundle\UIBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class MoveToChild extends Constraint
{
    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
