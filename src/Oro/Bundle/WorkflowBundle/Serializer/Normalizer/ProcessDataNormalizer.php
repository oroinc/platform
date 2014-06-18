<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessJobRepository;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ProcessDataNormalizer extends SerializerAwareNormalizer implements NormalizerInterface, DenormalizerInterface
{
    const SERIALIZED = '__SERIALIZED__';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param Registry $registry
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(Registry $registry, DoctrineHelper $doctrineHelper)
    {
        $this->registry = $registry;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $denormalizedData = array();

        foreach ($data as $key => $value) {
            if ($this->isEntityValue($value)) {
                $denormalizedData[$key] = $this->denormalizeEntity($value);
            } elseif ($this->isUnserializable($value)) {
                $denormalizedData[$key] = $this->unserialize($value);
            } elseif (is_array($value)) {
                $denormalizedData[$key] = $this->denormalize($value, $class, $format, $context);
            } else {
                $denormalizedData[$key] = $value;
            }
        }

        return new ProcessData($denormalizedData);
    }

    /**
     * Returns a completed entity
     *
     * @param array $objectData
     * @return object
     */
    protected function denormalizeEntity(array $objectData)
    {
        $className = $objectData['className'];

        if (!empty($objectData['entityId'])) {
            return $this->registry->getManagerForClass($className)->find($className, $objectData['entityId']);
        }

        $entityData = !empty($objectData['entityData']) ? $objectData['entityData'] : array();
        $classMetadata = $this->getClassMetadata($className);
        $entity = $classMetadata->getReflectionClass()->newInstanceWithoutConstructor();

        foreach ($entityData as $name => $value) {
            if ($this->isEntityValue($value)) {
                $value = $this->denormalizeEntity($value);
            } elseif ($this->isUnserializable($value)) {
                $value = $this->unserialize($value);
            }

            $reflectionProperty = $classMetadata->getReflectionProperty($name);
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($entity, $value);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $processJob = $this->getProcessJob($context);
        $entity = $object['entity'];
        if (!$entity) {
            throw new \LogicException('Process entity is not specified');
        }

        if ($processJob->getProcessTrigger()->getEvent() == ProcessTrigger::EVENT_DELETE) {
            $processJob->setEntityId(null);
        } else {
            $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
            $processJob->setEntityId($entityId);
        }

        $normalizedData = array();

        foreach ($object as $key => $value) {
            if (is_object($value)) {
                $normalizedData[$key] = $this->normalizeObject($value, $context);
            } elseif (is_array($value)) {
                $normalizedData[$key] = $this->normalize($value, $format, $context);
            } else {
                $normalizedData[$key] = $value;
            }
        }

        return $normalizedData;
    }

    /**
     * @param object $value
     * @param array $context
     * @return array
     */
    protected function normalizeObject($value, array $context = array())
    {
        if ($this->doctrineHelper->isManageableEntity($value)) {
            return $this->normalizeEntity($value, $context);
        } else {
            return $this->serialize($value);
        }
    }

    /**
     * @param object $entity
     * @param array $context
     * @return array
     */
    protected function normalizeEntity($entity, array $context = array())
    {
        $processJob = $this->getProcessJob($context);
        $entityClass = $this->getClass($entity);
        $normalizedData = array('className' => $entityClass);

        if ($processJob->getProcessTrigger()->getEvent() != ProcessTrigger::EVENT_DELETE) {
            $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
            $normalizedData['entityId'] = $entityId;

            return $normalizedData;
        }

        $classMetadata  = $this->getClassMetadata($entityClass);
        $fieldNames = $classMetadata->getFieldNames();

        $normalizedData['entityData'] = array();

        foreach ($fieldNames as $fieldName) {
            $fieldValue = $classMetadata->getFieldValue($entity, $fieldName);

            if (is_object($fieldValue)) {
                $fieldValue = $this->normalizeObject($fieldValue, $context);
            }

            $normalizedData['entityData'][$fieldName] = $fieldValue;
        }

        return $normalizedData;
    }

    /**
     * @param string $className
     * @return ClassMetadata
     */
    protected function getClassMetadata($className)
    {
        return $this->registry->getManagerForClass($className)->getClassMetadata($className);
    }

    /**
     * @return ProcessJobRepository
     */
    protected function getProcessJobRepository()
    {
        return $this->registry->getRepository('OroWorkflowBundle:ProcessJob');
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
     * @param mixed $value
     * @return array
     */
    protected function serialize($value)
    {
        return array(self::SERIALIZED => base64_encode(serialize($value)));
    }

    /**
     * @param array $value
     * @return mixed|null
     */
    protected function unserialize(array $value)
    {
        if (!$this->isUnserializable($value)) {
            return null;
        }

        $value = $value[self::SERIALIZED];

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
     * @param mixed $value
     * @return bool
     */
    protected function isUnserializable($value)
    {
        return is_array($value) && !empty($value[self::SERIALIZED]);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function isEntityValue($value)
    {
        return is_array($value) && !empty($value['className']);
    }

    /**
     * @param array $context
     * @return ProcessJob
     */
    protected function getProcessJob(array $context)
    {
        if (empty($context['processJob'])) {
            throw new \LogicException('Process job is not defined');
        }

        if (!$context['processJob'] instanceof ProcessJob) {
            throw new \LogicException('Invalid process job entity');
        }

        return $context['processJob'];
    }

    /**
     * @param object $entity
     * @return string
     */
    protected function getClass($entity)
    {
        return ClassUtils::getClass($entity);
    }
}
