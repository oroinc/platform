<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class MaxEntities extends Constraint
{
    public $message = 'test'; //@todo: message

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
