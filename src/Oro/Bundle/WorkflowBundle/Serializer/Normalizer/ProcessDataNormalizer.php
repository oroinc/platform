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

class ProcessDataNormalizer extends SerializerAwareNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @var ClassMetadata
     */
    protected $classMetadata;

    /**
     * @var Registry
     */
    protected $registry;

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
            return $this->prepareProcessData($denormalizedData, $processJob);
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
            if (empty($value)) {
                $denormalizedData[$key] = null;
            } elseif (!empty($value['className'])) {
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
     * @param string|object $className
     * @return ClassMetadata
     */
    protected function getClassMetadata($className)
    {
        if (!$this->classMetadata) {
            if (is_object($className)) {
                $className = ClassUtils::getClass($className);
            }
            $this->classMetadata = $this->registry->getManager()->getClassMetadata($className);
        }

        return $this->classMetadata;
    }

    /**
     * {@inheritdoc}
     * @param object|array $object
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $normalizedData = array();

        foreach ($object as $key => $value) {
            if (empty($value)) {
                $normalizedData[$key] = null;
            } elseif (is_object($value)) {
                $normalizedData[$key] = $this->normalizeEntity($value);
            } elseif (is_array($value)) {
                $normalizedData[$key] = $this->normalize($value, $format, $context);
            } else {
                $normalizedData[$key] = $value;
            }
        }

        return $normalizedData;
    }

    protected function normalizeEntity($entity)
    {
        $normalizedData = array(
            'className' => ClassUtils::getClass($entity),
            'classData' => array()
        );

        $reflection = $this->getClassMetadata($entity);
        $properties = $reflection->getReflectionProperties();

        /** @var $property \ReflectionProperty */
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $name  = $property->getName();
            $value = $property->getValue($entity);

            if ($value instanceof \DateTime) {
                $value = $this->serialize($value);
            }

            $normalizedData['classData'][$name] = is_object($value) ? null : $value;
        }

        return $normalizedData;
    }

    /**
     * @param array $processData
     * @param ProcessJob $processJob
     * @return ProcessData
     * @throws InvalidParameterException
     */
    protected function prepareProcessData($processData, $processJob)
    {
        $old = $new = null;
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
                $old = $processData['old'];
                $new = $processData['new'];
            // break intentionally omitted
            case ProcessTrigger::EVENT_CREATE:
                $repository = $this->registry->getRepository('OroWorkflowBundle:ProcessJob');
                return new ProcessData(array(
                    'entity' => $repository->findEntity($processJob),
                    'old'    => $old,
                    'new'    => $new
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
        return base64_encode(serialize($value));
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
        return is_object($data) && $this->supportsClass(ClassUtils::getClass($data));
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
        $reflectionProperty->setAccessible(false);
    }
}
