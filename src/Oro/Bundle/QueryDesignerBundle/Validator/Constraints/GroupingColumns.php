<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether a query definition created by the query designer
 * groups all columns without aggregate function if there is at least one column with aggregate function.
 */
class GroupingColumns extends Constraint
{
    /** @var string */
    public $message = 'oro.query_designer.grouping.column_exists';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
