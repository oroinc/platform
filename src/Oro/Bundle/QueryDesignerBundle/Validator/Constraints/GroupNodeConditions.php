<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether a group node does not contains both computed and uncomputed conditions.
 * @see \Oro\Bundle\QueryDesignerBundle\Model\ExpressionBuilder
 */
class GroupNodeConditions extends Constraint
{
    /** @var string */
    public $message = 'Computed conditions cannot be mixed with uncomputed.';
}
