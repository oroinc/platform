<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * Class ProcessDataNormalizer
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ProcessDataNormalizer extends SerializerAwareNormalizer implements NormalizerInterface, DenormalizerInterface
{
    const MAX_DEEP_LEVEL = 1;
    const SERIALIZED     = '__SERIALIZED__';
    /**
     * @var ClassMetadata
     */
    protected $classMetadata;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $managedEntities;

    /**
     * @var string
     */
    protected $currentEvent;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $denormalizedData = $this->denormalizeValues($data);

        if (!empty($context['processJob'])) {
            /** @var ProcessJob $processJob */
            $processJob = $context['processJob'];
            return $this->prepareDenormalizedData($denormalizedData, $processJob);
        } else {
            return new $class($denormalizedData);
        }
    }

    /**
     * Returns a completed entity
     *
     * @param string $className
     * @param array $attributes
     * @return object
     */
    protected function denormalizeEntity($className, $attributes = array())
    {
        $classMetadata = $this->getClassMetadata($className);
        $entity        = $classMetadata->getReflectionClass();

        foreach ($attributes as $name => $value) {
            if (is_array($value) && !empty($value[self::SERIALIZED])) {
                $value = $this->unserialize($value[self::SERIALIZED]);
            }
            $this->writeProperty($entity, $name, $value);
        }

        return $entity;
    }

    /**
     * @param array $values
     * @return array denormalized data
     */
    protected function denormalizeValues(array $values)
    {
        $denormalizedData = array();

        foreach ($values as $key => $value) {
            if (!empty($value['className'])) {
                $denormalizedData[$key] = $this->denormalizeEntity($value['className'], $value['classData']);
            } elseif (is_array($value)) {
                $denormalizedData[$key] = $this->denormalizeValues($value);
            } else {
                $denormalizedData[$key] = $value;
            }
        }

        return $denormalizedData;
    }

    /**
     * @param object $entity
     * @return string
     */
    protected function getClass($entity)
    {
        return ClassUtils::getClass($entity);
    }

    /**
     * @param string|object $className
     * @return ClassMetadata
     */
    protected function getClassMetadata($className)
    {
        if (!$this->classMetadata) {
            if (is_object($className)) {
                $className = $this->getClass($className);
            }
            $this->classMetadata = $this->registry->getManager()->getClassMetadata($className);
        }

        return $this->classMetadata;
    }

    protected function getEvent($context)
    {
        if (!$this->currentEvent && !empty($context['processJob'])) {
            /** @var ProcessJob $processJob */
            $processJob = $context['processJob'];
            $this->currentEvent = $processJob->getProcessTrigger()->getEvent();
        }

        return $this->currentEvent;
    }

    /**
     * @return \Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessJobRepository
     */
    protected function getRepository()
    {
        return $this->registry->getRepository('OroWorkflowBundle:ProcessJob');
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function isEntityManaged($entity)
    {
        if (!$this->managedEntities) {
            $allMetadata = $this->registry->getManager()->getMetadataFactory()->getAllMetadata();
            /** @var ClassMetadata $metadata */
            foreach ($allMetadata as $metadata) {
                $this->managedEntities[] = $metadata->getName();
            }
        }

        if (count($this->managedEntities) > 1) {
            return in_array($this->getClass($entity), $this->managedEntities);
        } else {
            return !empty($this->managedEntities) && reset($this->managedEntities) == $this->getClass($entity);
        }
    }

    /**
     * {@inheritdoc}
     * @param object|array $object
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $normalizedData = array();
        $currentEvent   = $this->getEvent($context);

        foreach ($object as $key => $value) {
            if ($currentEvent && $currentEvent != ProcessTrigger::EVENT_UPDATE && ('old' == $key || 'new' == $key)) {
                continue;
            }

            if (is_object($value)) {
                $normalizedData[$key] = $this->normalizeEntity($value, $context);
            } elseif (is_array($value)) {
                $normalizedData[$key] = $this->normalize($value, $format, $context);
            } else {
                $normalizedData[$key] = $value;
            }
        }

        return $normalizedData;
    }

    /**
     * @param object $entity
     * @param array $context
     * @param integer $deepLevel
     * @return array
     */
    protected function normalizeEntity($entity, $context, $deepLevel = 0)
    {
        $classMetadata  = $this->getClassMetadata($entity);
        $normalizedData = array(
            'className' => $this->getClass($entity),
            'classData' => array()
        );

        if (!empty($context['processJob'])) {
            /** @var ProcessJob $processJob */
            $processJob = $context['processJob'];

            if ($this->getEvent($context) != ProcessTrigger::EVENT_DELETE) {
                $identifierNames = $classMetadata->getIdentifierFieldNames();
                $normalizedData['classData'][reset($identifierNames)] = $processJob->getEntityId();
                return $normalizedData;
            }
        }

        $fieldNames = $classMetadata->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            $fieldValue = $classMetadata->getFieldValue($entity, $fieldName);

            if (is_object($fieldValue)) {
                if ($this->isEntityManaged($fieldValue) && $deepLevel <= self::MAX_DEEP_LEVEL) {
                    $fieldValue = $this->normalizeEntity($fieldValue, $context, $deepLevel + 1);
                } else {
                    $fieldValue = $this->serialize($fieldValue);
                }
            }

            $normalizedData['classData'][$fieldName] = $fieldValue;
        }

        return $normalizedData;
    }

    /**
     * @param array $processData
     * @param ProcessJob $processJob
     * @return ProcessData
     * @throws InvalidParameterException
     */
    protected function prepareDenormalizedData($processData, $processJob)
    {
        $triggerEvent = $processJob->getProcessTrigger()->getEvent();
        switch ($triggerEvent) {
            case ProcessTrigger::EVENT_DELETE:
                if (empty($processData['entity'])) {
                    throw new InvalidParameterException(
                        'Invalid process job data for the delete event. Entity can not be empty.'
                    );
                } elseif (!is_object($processData['entity'])) {
                    throw new InvalidParameterException(
                        'Invalid process job data for the delete event. Entity must be an object.'
                    );
                }
                return new ProcessData(array(
                    'entity' => $processData['entity'],
                    'old'    => null,
                    'new'    => null
                ));
            case ProcessTrigger::EVENT_UPDATE:
                return new ProcessData(array(
                    'entity' => $this->getRepository()->findEntity($processJob),
                    'old'    => $processData['old'],
                    'new'    => $processData['new']
                ));
            break;
            case ProcessTrigger::EVENT_CREATE:
                return new ProcessData(array(
                    'entity' => $this->getRepository()->findEntity($processJob),
                    'old'    => null,
                    'new'    => null
                ));
            default:
                throw new InvalidParameterException(sprintf('Got invalid or unregister event "%s"', $triggerEvent));
        }
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function serialize($value)
    {
        return array(self::SERIALIZED => base64_encode(serialize($value)));
    }

    /**
     * Checks if the given class is ProcessData or it's ancestor.
     *
     * @param string $class
     * @return boolean
     */
    protected function supportsClass($class)
    {
        $processDataClass = 'Oro\Bundle\WorkflowBundle\Model\ProcessData';
        return $processDataClass == $class ||
               (is_string($class) && class_exists($class) && in_array($processDataClass, class_parents($class)));
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $this->supportsClass($type);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && $this->supportsClass($this->getClass($data));
    }

    /**
     * @param string $value
     * @return mixed|null
     */
    protected function unserialize($value)
    {
        if (!is_string($value)) {
            return null;
        }

        $value = base64_decode($value);

        if (!is_string($value) || !$value) {
            return null;
        }

        return unserialize($value);
    }

    /**
     * @param object $class
     * @param string $propertyName
     * @param mixed $propertyValue
     */
    private function writeProperty($class, $propertyName, $propertyValue)
    {
        $reflectionProperty = $this->getClassMetadata($class)->getReflectionProperty($propertyName);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($class, $propertyValue);
    }
}
