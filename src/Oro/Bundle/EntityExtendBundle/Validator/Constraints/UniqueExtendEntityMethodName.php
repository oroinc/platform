<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueExtendEntityMethodName extends Constraint
{
    /** @var string  */
    public $message = 'Method for this value is already used. Please use another name.';

    /** @var string  */
    public $path = 'fieldName';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return UniqueExtendEntityMethodNameValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
