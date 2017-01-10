<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueExtendEntityMethodName extends Constraint
{
    /** @var string */
    public $message = 'The \'{{ value }}\' word is reserved for system purposes.';

    /** @var string */
    public $unexpectedNameMessage = 'The field name must be \'{{ field }}\'.';

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
