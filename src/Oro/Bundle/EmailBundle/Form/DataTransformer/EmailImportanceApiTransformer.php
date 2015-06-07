<?php

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

use Oro\Bundle\EmailBundle\Entity\Email;

class EmailImportanceApiTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
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

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
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
