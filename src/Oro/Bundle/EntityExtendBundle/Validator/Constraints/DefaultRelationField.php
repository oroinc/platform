<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class DefaultRelationField extends Constraint
{
    /** @var string */
    public $duplicateRelationMessage = 'This name is duplicated default field of relation.';

    /** @var string */
    public $duplicateFieldMessage = 'This relation name is duplicated a field.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return DefaultRelationFieldValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
