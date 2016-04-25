<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\DenormalizeEntityEvent;
use Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;

class ConfigurableEntityNormalizer extends AbstractContextModeAwareNormalizer implements SerializerAwareInterface
{
    const FULL_MODE  = 'full';
    const SHORT_MODE = 'short';

    /** @var SerializerInterface|NormalizerInterface|DenormalizerInterface */
    protected $serializer;

    /** @var FieldHelper */
    protected $fieldHelper;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param FieldHelper $fieldHelper
     */
    public function __construct(FieldHelper $fieldHelper)
    {
        $this->fieldHelper = $fieldHelper;

        parent::__construct([self::FULL_MODE, self::SHORT_MODE], self::FULL_MODE);
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     */
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
            if (array_key_exists($fieldName, $data)) {
                $value = $data[$fieldName];
                if ($data[$fieldName] !== null
                    && ($this->fieldHelper->isRelation($field) || $this->fieldHelper->isDateTimeField($field))
                ) {
                    if ($this->fieldHelper->isMultipleRelation($field)) {
                        $entityClass = sprintf('ArrayCollection<%s>', $field['related_entity_name']);
                    } elseif ($this->fieldHelper->isSingleRelation($field)) {
                        $entityClass = $field['related_entity_name'];
                    } else {
                        $entityClass = 'DateTime';
                        $context = array_merge($context, ['type' => $field['type']]);
                    }
                    $context = array_merge($context, ['fieldName' => $fieldName]);
                    $value = $this->serializer->denormalize($value, $entityClass, $format, $context);
                }

                $this->fieldHelper->setObjectValue($result, $fieldName, $value);
            }
        }

        return $this->dispatchDenormalizeEvent($data, $result, Events::AFTER_DENORMALIZE_ENTITY);
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

            $fieldValue = $this->fieldHelper->getObjectValue($object, $fieldName);
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
            $this->dispatcher->dispatch($eventName, new DenormalizeEntityEvent($result, $data));
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
            $this->dispatcher->dispatch($eventName, $event);

            return $event->getResult();
        }

        return $result;
    }
}
