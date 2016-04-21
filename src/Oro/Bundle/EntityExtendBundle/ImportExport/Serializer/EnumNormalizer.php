<?php

namespace Oro\Bundle\EntityExtendBundle\ImportExport\Serializer;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;

class EnumNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @param FieldHelper $fieldHelper
     */
    public function __construct(FieldHelper $fieldHelper)
    {
        $this->fieldHelper = $fieldHelper;
    }

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
            return $this->getShortData($object);
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
            'id' => empty($data['id']) ? null : $data['id'],
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

    /**
     * @param AbstractEnumValue $object
     * @return array
     */
    protected function getShortData(AbstractEnumValue $object)
    {
        if ($this->fieldHelper->getConfigValue(ClassUtils::getClass($object), 'name', 'identity')) {
            return ['name' => $object->getName()];
        } else {
            return ['id' => $object->getId()];
        }
    }
}
