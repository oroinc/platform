<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator;

use Symfony\Component\Validator\Constraint;

class NotBlankFilters extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.query_designer.condition_builder.filters.not_blank';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
