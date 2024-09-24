<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether a query definition created by the query designer has at least one filter.
 */
class NotEmptyFilters extends Constraint
{
    /** @var string */
    public $message = 'oro.query_designer.condition_builder.filters.not_empty';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
