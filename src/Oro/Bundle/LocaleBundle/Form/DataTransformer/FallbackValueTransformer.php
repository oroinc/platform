<?php

namespace Oro\Bundle\LocaleBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\LocaleBundle\Model\FallbackType;

class FallbackValueTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        $result = [
            'value' => null,
            'fallback' => null,
            'use_fallback' => true,
        ];

        if ($value instanceof FallbackType) {
            $result['fallback'] = $value->getType();
        } else {
            $result['use_fallback'] = false;
            $result['value'] = $value;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }

        if (!empty($value['fallback'])) {
            return new FallbackType($value['fallback']);
        } elseif (isset($value['value'])) {
            return $value['value'];
        }

        return null;
    }
}
