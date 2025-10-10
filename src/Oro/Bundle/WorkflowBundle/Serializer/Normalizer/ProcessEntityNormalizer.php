<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

/**
 * Handles serialization of Doctrine entities in workflow process jobs
 * Handles entity ID normalization for non-delete events and full entity data serialization for delete events
 */
class ProcessEntityNormalizer extends AbstractProcessNormalizer
{
    protected ManagerRegistry $registry;

    protected DoctrineHelper $doctrineHelper;

    public function __construct(ManagerRegistry $registry, DoctrineHelper $doctrineHelper)
    {
        $this->registry = $registry;
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        $processJob = $this->getProcessJob($context);
        $entityClass = $this->getClass($object);
        $normalizedData = ['className' => $entityClass];

        if ($processJob->getProcessTrigger()->getEvent() != ProcessTrigger::EVENT_DELETE) {
            $entityId = $this->doctrineHelper->getSingleEntityIdentifier($object);
            $normalizedData['entityId'] = $entityId;

            return $normalizedData;
        }

        $normalizedData['entityData'] = [];

        $classMetadata = $this->getClassMetadata($entityClass);
        $fieldNames = $classMetadata->getFieldNames();

        foreach ($fieldNames as $name) {
            $value = $classMetadata->getFieldValue($object, $name);
            $normalizedData['entityData'][$name] = $this->serializer->normalize($value, $format, $context);
        }

        return $normalizedData;
    }

    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        $className = $data['className'];

        if (!empty($data['entityId'])) {
            return $this->registry->getManagerForClass($className)->find($className, $data['entityId']);
        }

        $entityData = !empty($data['entityData']) ? $data['entityData'] : [];
        $classMetadata = $this->getClassMetadata($className);
        $entity = $classMetadata->getReflectionClass()->newInstanceWithoutConstructor();

        foreach ($entityData as $name => $value) {
            $value = $this->serializer->denormalize($value, '', $format, $context);

            $reflectionProperty = $classMetadata->getReflectionProperty($name);
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($entity, $value);
        }

        return $entity;
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && $this->doctrineHelper->isManageableEntity($data);
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && !empty($data['className']);
    }

    protected function getClassMetadata(string $className): ClassMetadata
    {
        return $this->registry->getManagerForClass($className)->getClassMetadata($className);
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true];
    }
}
