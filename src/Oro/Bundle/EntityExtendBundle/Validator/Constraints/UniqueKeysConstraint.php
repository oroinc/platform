<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueKeysConstraint extends Constraint
{
    public $message = 'Name and keys combination must be unique';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_entity_extend.validator.unique_keys';
    }
}
