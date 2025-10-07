<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms a value between a boolean and a string.
 */
class BooleanToStringTransformer implements DataTransformerInterface
{
    #[\Override]
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (!\is_bool($value)) {
            throw new TransformationFailedException('Expected a boolean.');
        }

        return $value ? 'true' : 'false';
    }

    #[\Override]
    public function reverseTransform($value)
    {
        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if ('' === $value) {
            return null;
        }

        return $this->transformStringToBoolean($value);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @throws TransformationFailedException if the given string cannot be converted to a boolean
     */
    private function transformStringToBoolean(string $value): bool
    {
        switch ($value) {
            case 'true':
            case 'True':
            case 'yes':
            case 'Yes':
            case '1':
                return true;
            case 'false':
            case 'False':
            case 'no':
            case 'No':
            case '0':
                return false;
        }

        throw new TransformationFailedException(\sprintf('"%s" cannot be converted to a boolean.', $value));
    }
}
