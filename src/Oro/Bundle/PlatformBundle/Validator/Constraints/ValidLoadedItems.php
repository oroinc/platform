<?php

/*
 * This file is a copy of {@see Symfony\Component\Validator\Constraints\All}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Bundle\PlatformBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * When applied to an array (or Traversable object), this constraint allows you to apply
 * a collection of constraints to each element of the array or each loaded element of PersistentCollection
 * The difference with Symfony constraint is that not loaded collection items are not validated.
 * @see \Symfony\Component\Validator\Constraints\All
 * @see \Symfony\Component\Validator\Constraints\AllValidator
 *
 * @Annotation
 */
#[Attribute]
class ValidLoadedItems extends Constraint
{
    /** @var array|null */
    public $constraints;

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
}
