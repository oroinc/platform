<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueExtendEntityMethodName extends Constraint
{
    /** @var string */
    public $message = <<<EOF
The "{{ value }}" word is reserved for system purposes. Please use another name.
EOF;

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
