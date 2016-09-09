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
            return $value->setUseFallback(!is_null($value->getFallback()));
        }

        return $value->setViewValue($value->getOwnValue());
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        /** @var EntityFieldFallbackValue $value */
        // set entity value to null, so entity will use fallback value
        if ($value->isUseFallback() && $value->getFallback()) {
            $value->setScalarValue(null);
            $value->setArrayValue(null);

            return $value;
        }

        // not fallback, so make sure we clean fallback field
        $value->setFallback(null);

        if (is_array($value->getViewValue())) {
            return $value->setArrayValue($value->getViewValue())->setScalarValue(null);
        }

        return $value->setScalarValue($value->getViewValue())->setArrayValue(null);
    }
}
