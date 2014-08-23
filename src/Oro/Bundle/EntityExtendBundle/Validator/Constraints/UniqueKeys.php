<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueKeys extends Constraint
{
    public $message = 'Name and keys combination should be unique.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_entity_extend.validator.unique_keys';
    }
}
