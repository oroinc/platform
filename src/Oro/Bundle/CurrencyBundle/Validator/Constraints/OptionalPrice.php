<?php

namespace Oro\Bundle\CurrencyBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\NotBlank;

class OptionalPrice extends NotBlank
{
    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT];
    }
}
