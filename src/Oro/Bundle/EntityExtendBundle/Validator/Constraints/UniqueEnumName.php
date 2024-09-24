<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * UniqueEnumName constraint
 *
 * @Annotation
 */
#[Attribute]
class UniqueEnumName extends Constraint
{
    public $message = 'An enum with this name already exist.';

    /** @var string */
    public $entityClassName;

    /** @var string */
    public $fieldName;

    #[\Override]
    public function validatedBy(): string
    {
        return UniqueEnumNameValidator::ALIAS;
    }

    #[\Override]
    public function getRequiredOptions(): array
    {
        return ['entityClassName', 'fieldName'];
    }
}
