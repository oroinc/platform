<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;

use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Symfony\Component\PropertyAccess\PropertyAccess;
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
        $result = $this->createObject($class, $data);
        $fields = $this->fieldProvider->getFields($class, true);

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if (array_key_exists($fieldName, $data)) {
                $value = $data[$fieldName];
                if ($this->fieldHelper->isRelation($field) || $field['type'] == 'datetime') {
                    if ($this->fieldHelper->isMultipleRelation($field)) {
                        $entityClass = sprintf('ArrayCollection<%s>', $field['related_entity_name']);
                    } elseif ($this->fieldHelper->isSingleRelation($field)) {
                        $entityClass = $field['related_entity_name'];
                    } else {
                        $entityClass = 'DateTime';
                    }
                    $value = $this->serializer->denormalize($value, $entityClass, $format, $context);
                }

                $this->setObjectValue($result, $fieldName, $value);
            }
        }

        return $result;
    }

    /**
     * Method can be overridden in normalizers for specific classes
     *
     * @param string $class
     * @param mixed $data
     * @return object
     */
    protected function createObject($class, &$data)
    {
        $reflection = new \ReflectionClass($class);
        if ($reflection->getConstructor()->getNumberOfRequiredParameters() > 0) {
            return $reflection->newInstanceWithoutConstructor();
        } else {
            return $reflection->newInstance();
        }
    }

    /**
     * @param object $object
     * @param string $fieldName
     * @param mixed $value
     * @throws \Exception
     */
    protected function setObjectValue($object, $fieldName, $value)
    {
        try {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $propertyAccessor->setValue($object, $fieldName, $value);
        } catch (\Exception $e) {
            $class = ClassUtils::getClass($object);
            if (property_exists($class, $fieldName)) {
                $reflection = new \ReflectionProperty($class, $fieldName);
                $reflection->setAccessible(true);
                $reflection->setValue($object, $value);
            } else {
                throw $e;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = array())
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
    public function supportsNormalization($data, $format = null, array $context = array())
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
                    'Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface',
                    'Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface'
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
