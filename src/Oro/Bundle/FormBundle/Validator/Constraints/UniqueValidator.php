<?php

/*
 * This file is a copy of {@see Symfony\Component\Validator\Constraints\UniqueValidator}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * This validator is basically a copy of {@see \Symfony\Component\Validator\Constraints\UniqueValidator},
 * but this validator adds the ability to get values from an object
 * and uninitialized lazy collections are not validated.
 */
class UniqueValidator extends ConstraintValidator
{
    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Unique) {
            throw new UnexpectedTypeException($constraint, Unique::class);
        }

        if (null === $value) {
            return;
        }

        if (!\is_array($value) && !$value instanceof \Traversable) {
            throw new UnexpectedValueException($value, 'array or Traversable');
        }

        if ($value instanceof AbstractLazyCollection && !$value->isInitialized()) {
            return;
        }

        $collectionElements = [];
        $normalizer = $constraint->normalizer;
        $fields = (array)$constraint->fields;
        foreach ($value as $element) {
            if ($fields) {
                $element = $this->reduceElementKeys($fields, $element);
                if (null !== $normalizer) {
                    $element = $normalizer($element);
                }
                if (!$element) {
                    continue;
                }
            } elseif (null !== $normalizer) {
                $element = $normalizer($element);
            }

            if (\in_array($element, $collectionElements, true)) {
                $this->context->buildViolation($constraint->message)
                    ->setCode(Unique::IS_NOT_UNIQUE)
                    ->addViolation();

                return;
            }

            $collectionElements[] = $element;
        }
    }

    private function reduceElementKeys(array $fields, array|object $element): array
    {
        $output = [];
        foreach ($fields as $field) {
            if (!\is_string($field)) {
                throw new UnexpectedTypeException($field, 'string');
            }

            if ($this->propertyAccessor->isReadable($element, $field)) {
                $output[$field] = $this->propertyAccessor->getValue($element, $field);
            }
        }

        return $output;
    }
}
