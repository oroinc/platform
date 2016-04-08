<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueExtendEntityMethodName extends Constraint
{
    /** @var string */
    public $message = <<<EOF
This field name cannot be used because it conflicts with {{ value }} method(s) of this entity. Please use another name.
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
