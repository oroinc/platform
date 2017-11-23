<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\Exception\UnexpectedTypeException;

class ArrayToStringTransformer extends AbstractArrayToStringTransformer
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value || array() === $value) {
            return '';
        }

        if (!is_array($value)) {
            throw new UnexpectedTypeException($value, 'array');
        }

        return $this->transformArrayToString($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (null === $value || '' === $value) {
            return array();
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        return $this->transformStringToArray($value);
    }
}
