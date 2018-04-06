<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

class ProcessEntityNormalizer extends AbstractProcessNormalizer
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param ManagerRegistry $registry
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ManagerRegistry $registry, DoctrineHelper $doctrineHelper)
    {
        $this->registry = $registry;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $processJob = $this->getProcessJob($context);
        $entityClass = $this->getClass($object);
        $normalizedData = array('className' => $entityClass);

        if ($processJob->getProcessTrigger()->getEvent() != ProcessTrigger::EVENT_DELETE) {
            $entityId = $this->doctrineHelper->getSingleEntityIdentifier($object);
            $normalizedData['entityId'] = $entityId;

            return $normalizedData;
        }

        $normalizedData['entityData'] = array();

        $classMetadata  = $this->getClassMetadata($entityClass);
        $fieldNames = $classMetadata->getFieldNames();

        foreach ($fieldNames as $name) {
            $value = $classMetadata->getFieldValue($object, $name);
            $normalizedData['entityData'][$name] = $this->serializer->normalize($value, $format, $context);
        }

        return $normalizedData;
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $className = $data['className'];

        if (!empty($data['entityId'])) {
            return $this->registry->getManagerForClass($className)->find($className, $data['entityId']);
        }

        $entityData = !empty($data['entityData']) ? $data['entityData'] : array();
        $classMetadata = $this->getClassMetadata($className);
        $entity = $classMetadata->getReflectionClass()->newInstanceWithoutConstructor();

        foreach ($entityData as $name => $value) {
            $value = $this->serializer->denormalize($value, null, $format, $context);

            $reflectionProperty = $classMetadata->getReflectionProperty($name);
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($entity, $value);
        }

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && $this->doctrineHelper->isManageableEntity($data);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_array($data) && !empty($data['className']);
    }

    /**
     * @param string $className
     * @return ClassMetadata
     */
    protected function getClassMetadata($className)
    {
        return $this->registry->getManagerForClass($className)->getClassMetadata($className);
    }
}
