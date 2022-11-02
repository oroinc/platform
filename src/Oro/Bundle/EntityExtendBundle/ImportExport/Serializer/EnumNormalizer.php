<?php

namespace Oro\Bundle\EntityExtendBundle\ImportExport\Serializer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

/**
 * Normalizer for enum entities.
 */
class EnumNormalizer implements ContextAwareNormalizerInterface, ContextAwareDenormalizerInterface
{
    protected FieldHelper $fieldHelper;

    public function __construct(FieldHelper $fieldHelper)
    {
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * @param AbstractEnumValue $object
     *
     * {@inheritdoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        if (!$object instanceof AbstractEnumValue) {
            return null;
        }

        if (!empty($context['mode']) && $context['mode'] === 'short') {
            return $this->getShortData($object);
        }

        return [
            'id' => $object->getId(),
            'name' => $object->getName(),
            'priority' => (int)$object->getPriority(),
            'is_default' => (bool)$object->isDefault(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $reflection = new \ReflectionClass($type);

        $args = [
            // isset is used instead of empty as $data['id'] could be "0"
            'id' => $data['id'] ?? null,
            'name' => $data['name'] ?? '',
            'priority' => empty($data['priority']) ? 0 : $data['priority'],
            'default' => !empty($data['default']),
        ];

        return $reflection->newInstanceArgs($args);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return is_a($type, AbstractEnumValue::class, true);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof AbstractEnumValue;
    }

    protected function getShortData(AbstractEnumValue $object): array
    {
        if ($this->fieldHelper->getConfigValue(ClassUtils::getClass($object), 'name', 'identity')) {
            return ['name' => $object->getName()];
        }

        return ['id' => $object->getId()];
    }
}
