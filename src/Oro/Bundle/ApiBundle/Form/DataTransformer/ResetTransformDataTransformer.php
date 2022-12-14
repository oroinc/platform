<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * This data transformer can be used to wrap another data transformer
 * when it is required to ignore transform() method of the wrapped data transformer.
 */
class ResetTransformDataTransformer implements DataTransformerInterface
{
    private DataTransformerInterface $transformer;

    public function __construct(DataTransformerInterface $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * {@inheritDoc}
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($value)
    {
        return $this->transformer->reverseTransform($value);
    }
}
