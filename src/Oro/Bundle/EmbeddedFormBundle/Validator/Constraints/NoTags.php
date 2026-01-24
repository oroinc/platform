<?php

namespace Oro\Bundle\EmbeddedFormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint that validates a value does not contain HTML tags.
 *
 * This constraint ensures that a property value does not contain any HTML tags,
 * which is useful for preventing HTML injection in user-submitted data. When validation
 * fails, it displays a message indicating that the value should not contain HTML tags.
 */
class NoTags extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Value should not contain HTML tags.';

    #[\Override]
    public function validatedBy(): string
    {
        return get_class($this).'Validator';
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
