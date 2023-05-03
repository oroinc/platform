<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * This data transformer is used to wrap all view transformers,
 * that allows API to correct handling of NULL and empty string values.
 * Also see the related changes:
 * @see \Oro\Bundle\ApiBundle\Form\Extension\EmptyDataExtension
 * @see \Oro\Bundle\ApiBundle\Form\ApiFormBuilder
 */
class NullValueTransformer implements DataTransformerInterface
{
    private DataTransformerInterface $transformer;

    public function __construct(DataTransformerInterface $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Gets the wrapped data transformer.
     */
    public function getInnerTransformer(): DataTransformerInterface
    {
        return $this->transformer;
    }

    /**
     * Sets the wrapped data transformer.
     */
    public function setInnerTransformer(DataTransformerInterface $transformer): void
    {
        $this->transformer = $transformer;
    }

    /**
     * {@inheritDoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return null;
        }

        return $this->transformer->transform($value);
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($value)
    {
        $result = $this->transformer->reverseTransform($value ?? '');
        if (null === $result && '' === $value) {
            $result = $value;
        }

        return $result;
    }
}
