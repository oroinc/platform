<?php

namespace Oro\Bundle\PlatformBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * DateEarlierThan constraint
 *
 * @Annotation
 */
#[Attribute]
class DateEarlierThan extends Constraint
{
    public $field;

    public $message = "This date should be earlier than {{ field }} date";

    #[\Override]
    public function getDefaultOption(): ?string
    {
        return 'field';
    }

    #[\Override]
    public function getRequiredOptions(): array
    {
        return ['field'];
    }
}
