<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class MasterEntity extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Add entity before setting it as master.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_entity_merge_master_entity_validator';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
