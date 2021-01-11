<?php

namespace Oro\Bundle\SegmentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether a segment query definition created by the query designer
 * has at least one column with exactly specified sorting when the records limit is specified.
 */
class NotEmptySorting extends Constraint
{
    /** @var string */
    public $message = 'oro.segment.validation.sorting';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
