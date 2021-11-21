<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check that the number of entities is less or equals to the max limit
 * of entities to merge.
 */
class MaxEntitiesCount extends Constraint
{
    public string $message = 'You can merge only {{ limit }} entities at once.';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
