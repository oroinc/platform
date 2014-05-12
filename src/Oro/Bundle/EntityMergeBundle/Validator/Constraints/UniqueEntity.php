<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueEntity extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Merge entities should be unique.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_entity_merge_unique_entity_validator';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
