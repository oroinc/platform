<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class GroupNodeConstraint extends Constraint
{
    /** @var string */
    public $mixedConditionsMessage = 'Computed conditions cannot be mixed with uncomputed.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'Oro\Bundle\QueryDesignerBundle\Validator\GroupNodeValidator';
    }
}
