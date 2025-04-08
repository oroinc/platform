<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint can be used to protect changing a value of a field or an association.
 * The changing a value is allowed if the previous value was NULL.
 */
class UnchangeableField extends Constraint
{
    public string $message = 'oro.form.unchangeable_field.error';

    /**
     * Indicates whether the checked entity can be moved to another linked entity.
     * The linked entity is an entity the checked association is referenced by.
     * This option makes sense only when this validation constraint is used for one-to-one or many-to-one associations.
     */
    public bool $allowChangeOwner = true;

    #[\Override]
    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
