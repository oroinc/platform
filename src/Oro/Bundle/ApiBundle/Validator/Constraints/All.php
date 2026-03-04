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
use Symfony\Component\Validator\Exception\MissingOptionsException;

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

    /**
     * @param Constraint|Constraint[]|null $constraints
     * @param string[]|null                $groups
     */
    public function __construct(mixed $constraints = null, ?array $groups = null, mixed $payload = null)
    {
        if (null === $constraints || [] === $constraints) {
            throw new MissingOptionsException(
                \sprintf('The options "constraints" must be set for constraint "%s".', static::class),
                ['constraints']
            );
        }

        if ($constraints instanceof Constraint || (\is_array($constraints) && array_is_list($constraints))) {
            $this->constraints = $constraints;
            parent::__construct(null, $groups, $payload);
        } else {
            parent::__construct($constraints, $groups, $payload);
        }
    }

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
