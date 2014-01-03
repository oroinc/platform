<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class ObjectToJsonTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (!is_object($value)) {
            throw new UnexpectedTypeException($value, 'object');
        }

        return json_encode($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (null === $value || '' === $value) {
            return new \stdClass();
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        return json_decode($value);
    }
}
