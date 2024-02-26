<?php

namespace Oro\Bundle\EntityExtendBundle\Attribute\ORM;

use Attribute;

/**
 * #[DiscriminatorValue] custom PHP attribute implementation
 */
#[Attribute(Attribute::TARGET_CLASS)]
class DiscriminatorValue
{
    public function __construct(private readonly ?string $value = null)
    {
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
