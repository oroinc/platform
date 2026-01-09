<?php

namespace Oro\Bundle\EntityBundle\Form\DataTransformer;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms entity field fallback values between the model and view formats.
 */
class EntityFieldFallbackTransformer implements DataTransformerInterface
{
    #[\Override]
    public function transform($value): mixed
    {
        return $value;
    }

    #[\Override]
    public function reverseTransform($value): mixed
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
