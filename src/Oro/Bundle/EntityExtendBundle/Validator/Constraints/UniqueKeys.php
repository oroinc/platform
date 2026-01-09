<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating that unique key definitions are unique.
 *
 * This constraint ensures that the combination of a unique key name and its field list
 * is unique within an entity. It prevents duplicate unique key definitions that would
 * be redundant or cause database constraint conflicts.
 */
class UniqueKeys extends Constraint
{
    public $message = 'Name and keys combination should be unique.';

    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_entity_extend.validator.unique_keys';
    }
}
