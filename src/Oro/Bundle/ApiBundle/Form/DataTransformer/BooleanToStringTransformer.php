<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class BooleanToStringTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (!is_bool($value)) {
            throw new TransformationFailedException('Expected a boolean.');
        }

        return $value ? 'true' : 'false';
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if ('' === $value) {
            return null;
        }

        return $this->transformStringToBoolean($value);
    }

    /**
     * @param string $value
     *
     * @return bool
     *
     * @throws TransformationFailedException if the given string cannot be converted to a boolean
     */
    protected function transformStringToBoolean($value)
    {
        switch ($value) {
            case 'true':
            case 'yes':
            case '1':
                return true;
            case 'false':
            case 'no':
            case '0':
                return false;
        }

        throw new TransformationFailedException(
            sprintf('"%s" cannot be converted to a boolean.', $value)
        );
    }
}
