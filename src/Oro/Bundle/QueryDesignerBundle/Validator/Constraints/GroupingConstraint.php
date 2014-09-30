<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class GroupingConstraint extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.query_designer.validation.grouping';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_query_designer.grouping_validator';
    }
}
