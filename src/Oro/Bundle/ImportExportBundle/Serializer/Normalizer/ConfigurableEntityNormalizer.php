<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Event\DenormalizeEntityEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Normalized data based on entity fields config
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigurableEntityNormalizer extends AbstractContextModeAwareNormalizer implements SerializerAwareInterface
{
    const FULL_MODE  = 'full';
    const SHORT_MODE = 'short';

    /** @var SerializerInterface|NormalizerInterface|DenormalizerInterface */
    protected $serializer;

    /** @var FieldHelper */
    protected $fieldHelper;

    /** @var DenormalizerInterface */
    protected $scalarFieldDenormalizer;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct(FieldHelper $fieldHelper)
    {
        $this->fieldHelper = $fieldHelper;

        parent::__construct([self::FULL_MODE, self::SHORT_MODE], self::FULL_MODE);
    }

    public function setScalarFieldDenormalizer(DenormalizerInterface $scalarFieldDenormalizer)
    {
        $this->scalarFieldDenormalizer = $scalarFieldDenormalizer;
    }

    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $result = $this->dispatchDenormalizeEvent(
            $data,
            $this->createObject($class),
            Events::BEFORE_DENORMALIZE_ENTITY
        );
        $fields = $this->fieldHelper->getFields($class, true);

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if (!\array_key_exists($fieldName, $data)) {
                continue;
            }

            $value = $data[$fieldName];
            $fieldContext = $context;
            if ($value !== null) {
                $fieldContext['fieldName'] = $fieldName;
                if ($this->fieldHelper->isRelation($field) || $this->fieldHelper->isDateTimeField($field)) {
                    if ($this->fieldHelper->isMultipleRelation($field)) {
                        $entityClass = sprintf('ArrayCollection<%s>', $field['related_entity_name']);
                    } elseif ($this->fieldHelper->isSingleRelation($field)) {
                        // if data for object value is empty array we should not create empty object
                        if ([] === $value) {
                            continue;
                        }
                        $entityClass = $field['related_entity_name'];
                    } else {
                        $entityClass = 'DateTime';
                        $fieldContext['type'] = $field['type'];
                    }
                    $value = $this->serializer->denormalize($value, $entityClass, $format, $fieldContext);
                } else {
                    $fieldContext['className'] = $class;
                    $value = $this->tryDenormalizesValueAsScalar($field, $fieldContext, $value, $format);
                }
            }

            $this->setObjectValue($result, $fieldName, $value);
        }

        return $this->dispatchDenormalizeEvent($data, $result, Events::AFTER_DENORMALIZE_ENTITY);
    }

    /**
     * Try denormalizes data back into internal representation of datatype in php
     *
     * @param array  $fieldConfig Field configuration
     * @param array  $context     Options available to the denormalizer
     * @param mixed  $value       Value to convert
     * @param string $format      Format the given data was extracted from
     *
     * @return mixed
     */
    protected function tryDenormalizesValueAsScalar(array $fieldConfig, array $context, $value, $format)
    {
        $fieldType = $fieldConfig['type'] ?? false;
        if (false === $fieldType) {
            return $value;
        }

        $fieldContext = \array_merge(
            $context,
            [ScalarFieldDenormalizer::CONTEXT_OPTION_SKIP_INVALID_VALUE => true]
        );

        if (!$this->scalarFieldDenormalizer->supportsDenormalization($value, $fieldType, $format, $fieldContext)) {
            return $value;
        }

        return $this->scalarFieldDenormalizer->denormalize($value, $fieldType, $format, $fieldContext);
    }

    /**
     * Method can be overridden in normalizers for specific classes
     *
     * @param string $class
     * @return object
     */
    protected function createObject($class)
    {
        $reflection  = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor && $constructor->getNumberOfRequiredParameters() > 0) {
            return $reflection->newInstanceWithoutConstructor();
        } else {
            return $reflection->newInstance();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return is_array($data) && class_exists($type) && $this->fieldHelper->hasConfig($type);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $entityName = ClassUtils::getClass($object);
        $fields = $this->fieldHelper->getFields($entityName, true);

        $result = $this->dispatchNormalize($object, [], $context, Events::BEFORE_NORMALIZE_ENTITY);
        foreach ($fields as $field) {
            $fieldName = $field['name'];

            if ($this->isFieldSkippedForNormalization($entityName, $fieldName, $context)) {
                continue;
            }

            $fieldValue = $this->getObjectValue($object, $fieldName);
            if (is_object($fieldValue)) {
                $fieldContext = $context;

                $fieldContext['fieldName'] = $fieldName;
                if (method_exists($object, 'getId')) {
                    $fieldContext['entityId'] = $object->getId();
                }

                $isFullMode = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'full');

                // Do not export relation in short mode if it does not contain identity fields
                if (!$isFullMode
                    && isset($field['related_entity_name'])
                    && $this->fieldHelper->hasConfig($field['related_entity_name'])
                    && !$this->hasIdentityFields($field['related_entity_name'])
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

                if ($this->fieldHelper->isDateTimeField($field)) {
                    $fieldContext['type'] = $field['type'];
                }

                $fieldValue = $this->serializer->normalize($fieldValue, $format, $fieldContext);
            }

            $result[$fieldName] = $fieldValue;
        }

        return $this->dispatchNormalize($object, $result, $context, Events::AFTER_NORMALIZE_ENTITY);
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @param array $context
     * @return bool
     */
    protected function isFieldSkippedForNormalization($entityName, $fieldName, array $context)
    {
        // Do not normalize excluded fields
        $isExcluded = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'excluded');

        // Do not normalize non identity fields for short mode
        $isNotIdentity = $this->getMode($context) === self::SHORT_MODE
            && !$this->fieldHelper->getConfigValue($entityName, $fieldName, 'identity');

        return $isExcluded || $isNotIdentity;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
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
        $fields = $this->fieldHelper->getFields($entityName, true);
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if ($this->fieldHelper->getConfigValue($entityName, $fieldName, 'identity')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $data
     * @param object $result
     * @param string $eventName
     *
     * @return object
     */
    protected function dispatchDenormalizeEvent($data, $result, $eventName)
    {
        if ($this->dispatcher && $this->dispatcher->hasListeners($eventName)) {
            $this->dispatcher->dispatch(new DenormalizeEntityEvent($result, $data), $eventName);
        }

        return $result;
    }

    /**
     * @param $object
     * @param $result
     * @param array $context
     * @param string $eventName
     *
     * @return array
     */
    protected function dispatchNormalize($object, $result, array $context, $eventName)
    {
        if ($this->dispatcher && $this->dispatcher->hasListeners($eventName)) {
            $event = new NormalizeEntityEvent($object, $result, $this->getMode($context) === static::FULL_MODE);
            $this->dispatcher->dispatch($event, $eventName);

            return $event->getResult();
        }

        return $result;
    }

    /**
     * @param object $object
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getObjectValue($object, $fieldName)
    {
        return $this->fieldHelper->getObjectValue($object, $fieldName);
    }

    /**
     * @param object $object
     * @param string $fieldName
     * @param mixed $value
     */
    protected function setObjectValue($object, $fieldName, $value)
    {
        $this->fieldHelper->setObjectValue($object, $fieldName, $value);
    }
}
