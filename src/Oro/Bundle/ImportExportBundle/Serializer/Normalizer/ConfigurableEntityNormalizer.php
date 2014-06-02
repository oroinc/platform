<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ContactNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /**
     * @var SerializerInterface|NormalizerInterface|DenormalizerInterface
     */
    protected $serializer;

    /**
     * @var EntityFieldProvider
     */
    protected $fieldProvider;

    /**
     * @var ConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @param EntityFieldProvider $fieldProvider
     * @param ConfigProviderInterface $configProvider
     */
    public function __construct(
        EntityFieldProvider $fieldProvider,
        ConfigProviderInterface $configProvider
    ) {
        $this->fieldProvider = $fieldProvider;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        // TODO: Implement denormalize() method.
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $entityName = ClassUtils::getRealClass($object);
        $fields = $this->fieldProvider->getFields($entityName, true);

        $result = array();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if ($this->getConfigValue($entityName, $fieldName, 'excluded')) {
                continue;
            }

            $fieldValue = $propertyAccessor->getValue($object, $fieldName);
            if (is_object($fieldValue)) {
                if ($this->isRelation($field)) {
                    if ($this->getConfigValue($entityName, $fieldName, 'full')) {
                        $context['mode'] = 'full';
                    } else {
                        $context['mode'] = 'short';
                    }
                }

                $fieldValue = $this->serializer->normalize($fieldValue, $format, $context);
            }

            $result[$fieldName] = $fieldValue;
        }

        return $result;
    }

    /**
     * @todo - move this helper methods to separate service
     *
     * @param string $entityName
     * @param string $fieldName
     * @param string $parameter
     * @param mixed $default
     * @return mixed|null
     */
    protected function getConfigValue($entityName, $fieldName, $parameter, $default = null)
    {
        if (!$this->configProvider->hasConfig($entityName, $fieldName)) {
            return $default;
        }

        $fieldConfig = $this->configProvider->getConfig($entityName, $fieldName);
        if (!$fieldConfig->has($parameter)) {
            return $default;
        }

        return $fieldConfig->get($parameter);
    }

    /**
     * @todo - move this helper methods to separate service
     *
     * @param array $field
     * @return bool
     */
    protected function isRelation(array $field)
    {
        return !empty($field['relation_type']) && !empty($field['related_entity_name']);
    }

    /**
     * @todo - move this helper methods to separate service
     *
     * @param array $field
     * @return bool
     */
    protected function isSingleRelation(array $field)
    {
        return $this->isRelation($field)
        && in_array($field['relation_type'], array('ref-one', 'oneToOne', 'manyToOne'));
    }

    /**
     * @todo - move this helper methods to separate service
     *
     * @param array $field
     * @return bool
     */
    protected function isMultipleRelation(array $field)
    {
        return $this->isRelation($field)
        && in_array($field['relation_type'], array('ref-many', 'oneToMany', 'manyToMany'));
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        if (is_object($data)) {
            $dataClass = ClassUtils::getRealClass($data);
            return $this->configProvider->hasConfig($dataClass);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }
}
