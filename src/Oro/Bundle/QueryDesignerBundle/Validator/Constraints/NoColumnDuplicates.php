<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether a query definition created by the query designer
 * does not contain column duplicates.
 */
class NoColumnDuplicates extends Constraint
{
    /** @var string */
    public $message = 'oro.query_designer.columns.duplicates';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
