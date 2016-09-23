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

        if (is_null($value->getFallback())) {
            return $value->setScalarValue($value->getOwnValue());
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value instanceof EntityFieldFallbackValue) {
            return $value;
        }

        // set entity value to null, so entity will use fallback value
        $fallbackId = $value->getFallback();
        if (isset($fallbackId)) {
            $value->setScalarValue(null);
            $value->setArrayValue(null);

            return $value;
        }

        // not fallback, so make sure we clean fallback field
        $value->setFallback(null);

        if (is_array($value->getScalarValue())) {
            return $value
                ->setArrayValue($value->getScalarValue())
                ->setScalarValue(null);
        }

        return $value->setArrayValue(null);
    }
}
