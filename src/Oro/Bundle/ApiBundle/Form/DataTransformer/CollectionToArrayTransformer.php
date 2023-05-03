<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms each element in a collection via a specific data transformer.
 */
class CollectionToArrayTransformer implements DataTransformerInterface
{
    private DataTransformerInterface $elementTransformer;
    private bool $useCollection;

    public function __construct(DataTransformerInterface $elementTransformer, bool $useCollection = true)
    {
        $this->elementTransformer = $elementTransformer;
        $this->useCollection = $useCollection;
    }

    /**
     * {@inheritDoc}
     */
    public function transform($value)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($value)
    {
        $value = '' === $value || null === $value
            ? []
            : (array)$value;

        $transformedValues = [];
        foreach ($value as $val) {
            $transformedValues[] = $this->elementTransformer->reverseTransform($val);
        }

        return $this->useCollection
            ? new ArrayCollection($transformedValues)
            : $transformedValues;
    }
}
