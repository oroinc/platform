<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * The data transformer that is used to validate data type of ArrayType form type.
 */
class ArrayDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritDoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($value)
    {
        if ('' === $value) {
            return null;
        }

        return $value;
    }
}
