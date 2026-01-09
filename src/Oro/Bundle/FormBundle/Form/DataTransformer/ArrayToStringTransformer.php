<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms a value between an array and its string representation and vise versa.
 */
class ArrayToStringTransformer extends AbstractArrayToStringTransformer
{
    private bool $allowNull;

    public function __construct(string $delimiter, bool $filterUniqueValues, bool $allowNull = false)
    {
        parent::__construct($delimiter, $filterUniqueValues);
        $this->allowNull = $allowNull;
    }

    #[\Override]
    public function transform($value): mixed
    {
        if (null === $value || [] === $value) {
            return '';
        }

        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        return $this->transformArrayToString($value);
    }

    #[\Override]
    public function reverseTransform($value): mixed
    {
        if ('' === $value || '[]' === $value || null === $value) {
            return $this->allowNull ? null : [];
        }

        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        return $this->transformStringToArray($value);
    }
}
