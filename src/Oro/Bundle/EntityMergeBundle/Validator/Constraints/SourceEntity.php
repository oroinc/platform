<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check that the list of entities contains source entities for all fields.
 */
class SourceEntity extends Constraint
{
    public string $message = 'Add entity before setting it as a source entity for {{ field }} field.';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
