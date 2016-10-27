<?php

namespace Oro\Bundle\NavigationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class MaxNestedLevel extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
