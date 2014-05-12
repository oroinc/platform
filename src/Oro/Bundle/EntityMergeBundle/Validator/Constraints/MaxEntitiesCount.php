<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class MaxEntitiesCount extends Constraint
{
    /**
     * @var string
     */
    public $message = 'You can merge only {{ limit }} entities at once.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_entity_merge_max_entities_validator';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
