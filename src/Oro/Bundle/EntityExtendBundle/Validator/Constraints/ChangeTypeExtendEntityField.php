<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ChangeTypeExtendEntityField extends Constraint
{
    /** @var string */
    public $message = 'oro.entity_extend.change_type_not_allowed.message';

    #[\Override]
    public function validatedBy(): string
    {
        return ChangeTypeExtendEntityFieldValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
