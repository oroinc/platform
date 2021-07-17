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

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value || [] === $value) {
            return '';
        }

        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        return $this->transformArrayToString($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
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
