<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Serializer;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;

class EntityFieldNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ManagerRegistry $registry
     */
    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param AbstractEnumValue $object
     *
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$object instanceof FieldConfigModel) {
            return null;
        }

        if (!empty($context['mode']) && $context['mode'] === 'short') {
            return ['id' => $object->getId()];
        }

        $result = [
            'id' => $object->getId(),
            'fieldName' => $object->getFieldName(),
            'type' => $object->getType(),
        ];

        foreach ($this->configManager->getProviders() as $provider) {
            $scope = $provider->getScope();
            $values = $object->toArray($scope);

            foreach ($values as $code => $value) {
                $result[sprintf('%s.%s', $scope, $code)] = $value;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $reflection  = new \ReflectionClass($class);

        $args = [
            'field_name' => empty($data['fieldName']) ? '' : $data['fieldName'],
            'type' => empty($data['type']) ? '' : $data['type'],
        ];
        $entityId = empty($data['entity']['id']) ? null : $data['entity']['id'];
        /** @var FieldConfigModel $field */
        $field = $reflection->newInstanceArgs($args);

        if ($entityId) {
            $entityClassName = 'Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel';
            $entity = $this->registry->getManagerForClass($entityClassName)->find($entityClassName, $entityId);
            $field->setEntity($entity);
        }
        $field->setCreated(new \DateTime());

        return $field;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return is_a($type, 'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel', true);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof FieldConfigModel;
    }
}
