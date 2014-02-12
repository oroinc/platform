<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class MaxEntitiesCount extends Constraint
{
    public $message = 'You can merge only %max_count% entities at once';

    /**
     * {inheritdoc}
     */
    public function validatedBy()
    {
        return 'max_entities_validator';
    }

    /**
     * {inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
