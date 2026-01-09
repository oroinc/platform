<?php

namespace Oro\Bundle\EmbeddedFormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class NoTags extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Value should not contain HTML tags.';

    #[\Override]
    public function validatedBy(): string
    {
        return get_class($this) . 'Validator';
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
