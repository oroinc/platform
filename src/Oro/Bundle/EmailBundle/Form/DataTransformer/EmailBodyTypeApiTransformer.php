<?php

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Transforms between boolean values and text/html format strings.
 */
class EmailBodyTypeApiTransformer implements DataTransformerInterface
{
    #[\Override]
    public function transform($value): mixed
    {
        if (null === $value) {
            return '';
        }

        return $value ? 'text' : 'html';
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
            case 'text':
                return true;
            case 'html':
                return false;
            default:
                throw new InvalidArgumentException(
                    sprintf('Expected values "text" or "html", "%s" given', $value)
                );
        }
    }
}
