<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeGuid;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * This data transformer is used to validate that a GUID value has a valid format.
 */
class GuidDataTransformer implements DataTransformerInterface
{
    #[\Override]
    public function transform($value): mixed
    {
        if (null === $value) {
            return '';
        }

        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }
        if ('' === $value) {
            throw new TransformationFailedException('Expected a not empty string.');
        }

        return $value;
    }

    #[\Override]
    public function reverseTransform($value): mixed
    {
        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if ('' === $value) {
            return null;
        }

        if (!$this->isValidGuid($value)) {
            throw new TransformationFailedException('The value is not a valid GUID.');
        }

        return $value;
    }

    private function isValidGuid(string $value): bool
    {
        return (bool)preg_match('/^' . NormalizeGuid::REQUIREMENT . '$/i', $value);
    }
}
