<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class MinEntitiesCount extends Constraint
{
    const MIN_ENTITIES_COUNT = 2;

    public $message = 'You need minimum %min_count% entities to merge';

    /**
     * {inheritdoc}
     */
    public function validatedBy()
    {
        return 'min_entities_validator';
    }

    /**
     * {inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
