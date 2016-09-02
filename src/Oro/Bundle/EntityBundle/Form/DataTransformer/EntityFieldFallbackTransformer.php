<?php

namespace Oro\Bundle\EntityBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;

class EntityFieldFallbackTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (!$value instanceof EntityFieldFallbackValue) {
            return $value;
        }

        if (!is_null($value->getFallback())) {
            $value->setUseFallback(!is_null($value->getFallback()));
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        /** @var EntityFieldFallbackValue $value */
        // set entity value to null, so entity will use fallback value
        if ($value->isUseFallback() && $value->getFallback()) {
            $value->setStringValue(null);
        }

        // Set fallback to null so the entity will use it's own value
        if (!$value->isUseFallback() && !is_null($value->getStringValue())) {
            $value->setFallback(null);
        }

        return $value;
    }
}
