<?php

namespace Oro\Bundle\CurrencyBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Validation constraint for optional price values.
 *
 * This constraint extends {@see NotBlank} to validate {@see Price} entities, ensuring that if a
 * monetary value is provided, it must also have an associated currency. It operates
 * at the class level to validate the relationship between the price value and currency
 * fields, preventing incomplete price data.
 */
class OptionalPrice extends NotBlank
{
    #[\Override]
    public function getTargets(): string|array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
