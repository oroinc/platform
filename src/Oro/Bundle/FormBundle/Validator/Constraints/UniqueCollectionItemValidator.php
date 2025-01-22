<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * This validator checks that a collection has only unique items.
 */
class UniqueCollectionItemValidator extends ConstraintValidator
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
        if (!$constraint instanceof UniqueCollectionItem) {
            throw new UnexpectedTypeException($constraint, UniqueCollectionItem::class);
        }

        if (!\is_object($value)) {
            return;
        }

        $collection = $this->propertyAccessor->isReadable($value, $constraint->collection)
            ? $this->propertyAccessor->getValue($value, $constraint->collection)
            : null;
        if (null === $collection) {
            return;
        }

        if (!\is_array($collection) && !$collection instanceof \Traversable) {
            throw new UnexpectedValueException($collection, 'array or Traversable');
        }

        $valueKey = $this->getItemKey($value, $constraint->fields);
        if (!$valueKey) {
            return;
        }

        foreach ($collection as $item) {
            if ($value === $item) {
                continue;
            }
            $itemKey = $this->getItemKey($item, $constraint->fields);
            if (!$itemKey) {
                continue;
            }

            if ($itemKey === $valueKey) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();

                return;
            }
        }
    }

    private function getItemKey(object $item, array $fields): array
    {
        $result = [];
        foreach ($fields as $field) {
            if ($this->propertyAccessor->isReadable($item, $field)) {
                $result[$field] = $this->propertyAccessor->getValue($item, $field);
            }
        }

        return $result;
    }
}
