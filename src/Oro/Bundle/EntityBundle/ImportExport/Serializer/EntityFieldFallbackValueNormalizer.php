<?php

namespace Oro\Bundle\EntityBundle\ImportExport\Serializer;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;

/**
 * Serializer implementation for EntityFieldFallbackValue class
 */
class EntityFieldFallbackValueNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param EntityFieldFallbackValue $object
     *
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$object instanceof EntityFieldFallbackValue) {
            return null;
        }

        return [
            'fallback' => $object->getFallback(),
            'value' => $object->getOwnValue()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $object = new EntityFieldFallbackValue();
        if (!is_array($data)) {
            $data = ['value' => $data];
        }
        if (isset($data['fallback'])) {
            $object->setFallback($data['fallback']);
        }
        $value = $data['value'];
        if (is_array($data['value'])) {
            $object->setArrayValue($value);
        } else {
            $object->setScalarValue($value);
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return is_a($type, EntityFieldFallbackValue::class, true);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof EntityFieldFallbackValue;
    }
}
