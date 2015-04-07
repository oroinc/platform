<?php

namespace Oro\Bundle\EntityExtendBundle\ImportExport\Serializer;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;

class EnumNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param AbstractEnumValue $object
     *
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$object instanceof AbstractEnumValue) {
            return null;
        }

        if (!empty($context['mode']) && $context['mode'] === 'short') {
            return ['id' => $object->getId()];
        }

        return [
            'id' => $object->getId(),
            'name' => $object->getName(),
            'priority' => (int)$object->getPriority(),
            'is_default' => (bool)$object->isDefault()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $reflection  = new \ReflectionClass($class);

        $args = [
            'id' => $data['id'],
            'name' => empty($data['name']) ? '' : $data['name'],
            'priority' => empty($data['priority']) ? 0 : $data['priority'],
            'default' => !empty($data['default'])
        ];

        return $reflection->newInstanceArgs($args);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return is_a($type, 'Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue', true);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof AbstractEnumValue;
    }
}
