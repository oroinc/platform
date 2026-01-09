<?php

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Oro\Bundle\EmailBundle\Entity\Email;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Transforms email importance between integer constants and API string values.
 */
class EmailImportanceApiTransformer implements DataTransformerInterface
{
    #[\Override]
    public function transform($value): mixed
    {
        if (null === $value) {
            return '';
        }

        if (!is_int($value)) {
            throw new UnexpectedTypeException($value, 'integer');
        }

        switch ($value) {
            case Email::NORMAL_IMPORTANCE:
                return 'normal';
            case Email::HIGH_IMPORTANCE:
                return 'high';
            case Email::LOW_IMPORTANCE:
                return 'low';
            default:
                throw new InvalidArgumentException(
                    sprintf('Expected values 0, 1 or -1, %d given', $value)
                );
        }
    }

    #[\Override]
    public function reverseTransform($value): mixed
    {
        if (!$value) {
            return null;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        switch ($value) {
            case 'normal':
                return Email::NORMAL_IMPORTANCE;
            case 'high':
                return Email::HIGH_IMPORTANCE;
            case 'low':
                return Email::LOW_IMPORTANCE;
            default:
                throw new InvalidArgumentException(
                    sprintf('Expected values "normal", "high" or "low", "%s" given', $value)
                );
        }
    }
}
