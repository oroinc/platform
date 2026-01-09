<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * The data transformer that is used to validate data type of ArrayType form type.
 */
class ArrayDataTransformer implements DataTransformerInterface
{
    #[\Override]
    public function transform($value): mixed
    {
        if (null === $value) {
            return '';
        }

        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        return $value;
    }

    #[\Override]
    public function reverseTransform($value): mixed
    {
        if ('' === $value) {
            return null;
        }

        return $value;
    }
}
