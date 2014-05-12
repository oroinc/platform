<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class SourceEntity extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Add entity before setting it as a source entity for {{ field }} field.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_entity_merge_source_entity_validator';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
