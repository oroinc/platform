<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueExtendEntityField extends Constraint
{
    /** @var string  */
    public $message = 'This value is already used.';

    /** @var string  */
    public $path = 'fieldName';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_entity_extend.validator.unique_extend_entity_field';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
