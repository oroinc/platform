<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * This class allows array as empty data for Select2 type.
 */
class Select2ArrayToStringTransformerDecorator implements DataTransformerInterface
{
    /**
     * @var DataTransformerInterface
     */
    private $transformer;

    /**
     * @param DataTransformerInterface $transformer
     */
    public function __construct(DataTransformerInterface $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $this->transformer->transform($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (is_array($value)) {
            return $value;
        }

        return $this->transformer->reverseTransform($value);
    }
}
