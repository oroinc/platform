<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueExtendEntityField extends Constraint
{
    /** @var string */
    public $sameFieldMessage = 'A field with this name is already exist.';

    /** @var string */
    public $similarFieldMessage = 'This name conflicts with existing \'{{ field }}\' field.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return UniqueExtendEntityFieldValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
