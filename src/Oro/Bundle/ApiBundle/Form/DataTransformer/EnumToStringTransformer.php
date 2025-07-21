<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms a value between a PHP enum and a string.
 */
class EnumToStringTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly string $enumClass
    ) {
    }

    #[\Override]
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof $this->enumClass) {
            throw new TransformationFailedException(\sprintf('Expected an instance of "%s".', $this->enumClass));
        }

        return $value->name;
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

        $reflEnum = new \ReflectionEnum($this->enumClass);
        try {
            $reflCase = $reflEnum->getCase($value);
        } catch (\ReflectionException) {
            throw new TransformationFailedException('The value is not valid.');
        }

        return $reflCase->getValue();
    }
}
