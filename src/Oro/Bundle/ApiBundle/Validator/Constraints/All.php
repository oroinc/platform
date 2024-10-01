<?php

/*
 * This file is a copy of {@see Symfony\Component\Validator\Constraints\All}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Composite;

/**
 * When applied to an array (or Traversable object), this constraint allows to apply
 * a collection of constraints to each element of the array.
 * The difference with Symfony constraint is that uninitialized lazy collection is not validated.
 * @see \Symfony\Component\Validator\Constraints\All
 * @see \Symfony\Component\Validator\Constraints\AllValidator
 *
 * @Annotation
 */
#[Attribute]
class All extends Composite
{
    /** @var Constraint|Constraint[] */
    public $constraints = [];

    #[\Override]
    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }

    #[\Override]
    public function getDefaultOption(): ?string
    {
        return 'constraints';
    }

    #[\Override]
    public function getRequiredOptions(): array
    {
        return ['constraints'];
    }

    #[\Override]
    protected function getCompositeOption(): string
    {
        return 'constraints';
    }
}
