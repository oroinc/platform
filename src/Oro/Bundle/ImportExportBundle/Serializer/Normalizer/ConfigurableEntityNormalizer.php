<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;

use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

class ConfigurableEntityNormalizer extends AbstractContextModeAwareNormalizer implements SerializerAwareInterface
{
    const FULL_MODE  = 'full';
    const SHORT_MODE = 'short';

    /**
     * @var SerializerInterface|NormalizerInterface|DenormalizerInterface
     */
    protected $serializer;

    /**
     * @var EntityFieldProvider
     */
    protected $fieldProvider;

    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @param EntityFieldProvider $fieldProvider
     * @param FieldHelper $fieldHelper
     */
    public function __construct(
        EntityFieldProvider $fieldProvider,
        FieldHelper $fieldHelper
    ) {
        $this->fieldProvider = $fieldProvider;
        $this->fieldHelper = $fieldHelper;

        parent::__construct(array(self::FULL_MODE, self::SHORT_MODE), self::FULL_MODE);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $result = new $class();
        $fields = $this->fieldProvider->getFields($class, true);
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if (array_key_exists($fieldName, $data)) {
                $value = $data[$fieldName];
                if (empty($field['type']) || $field['type'] == 'datetime') {
                    if ($field['type'] == 'datetime') {
                        $relatedEntityClass = '\DateTime';
                    } else {
                        $relatedEntityClass = $field['related_entity_type'];
                    }
                    $value = $this->serializer->denormalize($value, $relatedEntityClass, $format, $context);
                }

                $propertyAccessor->setValue($result, $fieldName, $value);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_array($data) && class_exists($type) && $this->fieldHelper->hasConfig($type);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $entityName = ClassUtils::getClass($object);
        $fields = $this->fieldProvider->getFields($entityName, true);

        $result = array();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($fields as $field) {
            $fieldName = $field['name'];

            // Do not normalize excluded fields
            if ($this->fieldHelper->getConfigValue($entityName, $fieldName, 'excluded')) {
                continue;
            }
            // Do not normalize non identity fields for short mode
            if ($this->getMode($context) == self::SHORT_MODE
                && !$this->fieldHelper->getConfigValue($entityName, $fieldName, 'identity')) {
                continue;
            }

            $fieldValue = $propertyAccessor->getValue($object, $fieldName);
            if (is_object($fieldValue)) {
                $fieldContext = $context;
                $isFullMode = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'full');

                // Do not export relation in short mode if it does not contain identity fields
                if (!$isFullMode
                    && isset($field['related_entity_type'])
                    && $this->fieldHelper->hasConfig($field['related_entity_type'])
                    && !$this->hasIdentityFields($field['related_entity_type'])
                ) {
                    continue;
                }

                if ($this->fieldHelper->isRelation($field)) {
                    if ($isFullMode) {
                        $fieldContext['mode'] = self::FULL_MODE;
                    } else {
                        $fieldContext['mode'] = self::SHORT_MODE;
                    }
                }

                $fieldValue = $this->serializer->normalize($fieldValue, $format, $fieldContext);
            }

            $result[$fieldName] = $fieldValue;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        if (is_object($data)) {
            $dataClass = ClassUtils::getClass($data);
            return $this->fieldHelper->hasConfig($dataClass);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        if (!$serializer instanceof NormalizerInterface || !$serializer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Serializer must implement "%s" and "%s"',
                    'Symfony\Component\Serializer\Normalizer\NormalizerInterface',
                    'Symfony\Component\Serializer\Normalizer\DenormalizerInterface'
                )
            );
        }
        $this->serializer = $serializer;
    }

    /**
     * @param string $entityName
     * @return bool
     */
    protected function hasIdentityFields($entityName)
    {
        $fields = $this->fieldProvider->getFields($entityName);
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if ($this->fieldHelper->getConfigValue($entityName, $fieldName, 'identity')) {
                return true;
            }
        }

        return false;
    }
}
