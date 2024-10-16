<?php

namespace Oro\Bundle\CurrencyBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\NotBlank;

class OptionalPrice extends NotBlank
{
    #[\Override]
    public function getTargets(): string|array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
