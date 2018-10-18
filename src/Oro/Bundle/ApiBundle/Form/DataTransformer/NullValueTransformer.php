<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * This data transformer is used to wrap all view transformers,
 * that allows Data API to correct handling of NULL and empty string values.
 * Also see the related changes:
 * @see \Oro\Bundle\ApiBundle\Form\Extension\EmptyDataExtension
 * @see \Oro\Bundle\ApiBundle\Form\ApiFormBuilder
 */
class NullValueTransformer implements DataTransformerInterface
{
    /** @var DataTransformerInterface */
    private $transformer;

    /**
     * @param DataTransformerInterface $transformer
     */
    public function __construct(DataTransformerInterface $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Gets the wrapped data transformer.
     *
     * @return DataTransformerInterface
     */
    public function getInnerTransformer()
    {
        return $this->transformer;
    }

    /**
     * Sets the wrapped data transformer.
     *
     * @param DataTransformerInterface $transformer
     */
    public function setInnerTransformer(DataTransformerInterface $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return $value;
        }

        return $this->transformer->transform($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $result = $this->transformer->reverseTransform(null !== $value ? $value : '');
        if (null === $result && '' === $value) {
            $result = $value;
        }

        return $result;
    }
}
