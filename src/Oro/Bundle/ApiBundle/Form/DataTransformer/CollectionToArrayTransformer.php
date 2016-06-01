<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\DataTransformerInterface;

class CollectionToArrayTransformer implements DataTransformerInterface
{
    /** @var DataTransformerInterface */
    protected $elementTransformer;

    /**
     * @param DataTransformerInterface $elementTransformer
     */
    public function __construct(DataTransformerInterface $elementTransformer)
    {
        $this->elementTransformer = $elementTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $value = '' === $value || null === $value
            ? []
            : (array)$value;

        return new ArrayCollection(
            array_map(
                function ($element) {
                    return $this->elementTransformer->reverseTransform($element);
                },
                $value
            )
        );
    }
}
