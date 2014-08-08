<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class QueryConstraint extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Invalid query';

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
        return 'oro_query_designer.query_validator';
    }
}
