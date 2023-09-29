<?php

namespace Oro\Bundle\AttachmentBundle\Strategy;

use Doctrine\ORM\Mapping\MappingException;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\DefaultUnidirectionalFieldAuditStrategyProcessor;
use ReflectionException;

/**
 * This class add attachment change set to related entity as an auditable field intending to append to target.
 */
class AttachmentAuditStrategyProcessor extends DefaultUnidirectionalFieldAuditStrategyProcessor
{
    /**
     * @throws MappingException
     * @throws ReflectionException
     */
    public function processInverseCollections(array $sourceEntityData): array
    {
        $sourceEntityId = $sourceEntityData['entity_id'];
        $sourceEntityClass = $sourceEntityData['entity_class'];
        $sourceEntityManager = $this->doctrine->getManagerForClass($sourceEntityClass);

        $sourceEntityMeta = $sourceEntityManager->getClassMetadata($sourceEntityClass);
        $sourceEntity = $sourceEntityManager->find($sourceEntityClass, $sourceEntityId);

        if ($sourceEntity) {
            $getValueClosure = fn ($entity, $fieldName, $entityMetadata, $entityData) =>
                $entityMetadata->getFieldValue($entity, $fieldName);
        } else {
            $getValueClosure = fn ($entity, $fieldName, $entityMetadata, $entityData) =>
                $this->returnValueFromSourceData($entityData, $fieldName);
        }

        return $this->processEntityAssociationsFromCollection(
            $sourceEntityMeta,
            $sourceEntity,
            $sourceEntityData,
            $getValueClosure
        );
    }

    private function returnValueFromSourceData(array $sourceEntityData, string $fieldName): ?object
    {
        if ($sourceEntityData['change_set']) {
            foreach ($sourceEntityData['change_set'] as $field => $value) {
                if ($field === $fieldName && isset($value[0])) {
                    $valueId = $value[0]['entity_id'] ?? null;
                    $valueClass = $value[0]['entity_class'] ?? null;
                    if ($valueId !== null && $valueClass !== null) {
                        return $this->doctrine->getRepository($valueClass)->find($valueId);
                    }
                }
            }
        }

        return null;
    }
}
