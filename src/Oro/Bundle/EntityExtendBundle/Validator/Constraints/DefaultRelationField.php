<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class DefaultRelationField extends Constraint
{
    /** @var string */
    public $duplicateRelationMessage = 'This name is duplicated default field of relation.';

    /** @var string */
    public $duplicateFieldMessage = 'This relation name is duplicated a field.';

    #[\Override]
    public function validatedBy(): string
    {
        return DefaultRelationFieldValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
