<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ChangeTypeExtendEntityField extends Constraint
{
    /** @var string */
    public $message = 'oro.entity_extend.change_type_not_allowed.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return ChangeTypeExtendEntityFieldValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
