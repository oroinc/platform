<?php

namespace Oro\Bundle\DataAuditBundle\Strategy\Processor;

use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Helper\UnidirectionalFieldHelper;
use Oro\Bundle\MakerBundle\Helper\TranslationHelper;
use ReflectionException;

/**
 * This class add change set for entity that only has unidirectional relationship to its related entity.
 * Class that has unidirectional relationship with target entity can extend this to append audit log.
 */
class DefaultUnidirectionalFieldAuditStrategyProcessor implements EntityAuditStrategyProcessorInterface
{
    protected ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @throws MappingException
     * @throws ReflectionException
     */
    public function processInverseCollections(array $sourceEntityData): array
    {
        $fieldData = [];
        $sourceEntityId = $sourceEntityData['entity_id'];
        $sourceEntityClass = $sourceEntityData['entity_class'];
        $sourceEntityManager = $this->doctrine->getManagerForClass($sourceEntityClass);

        $sourceEntityMeta = $sourceEntityManager->getClassMetadata($sourceEntityClass);
        $sourceEntity = $sourceEntityManager->find($sourceEntityClass, $sourceEntityId);

        if ($sourceEntity) {
            $fieldData = $this->processEntityAssociationsFromCollection(
                $sourceEntityMeta,
                $sourceEntity,
                $sourceEntityData,
                fn ($entity, $fieldName, $entityMetadata, $entityData) =>
                    $entityMetadata->getFieldValue($entity, $fieldName)
            );
        }

        return $fieldData;
    }

    /**
     * @throws MappingException|ReflectionException
     */
    protected function processEntityAssociationsFromCollection(
        ClassMetadata $sourceEntityMeta,
        ?object       $sourceEntity,
        array         $sourceEntityData,
        \Closure      $getValueCallback
    ): ?array {
        $fieldsData = [];

        foreach ($sourceEntityMeta->associationMappings as $sourceFieldName => $sourceFieldValue) {
            $targetClass = $sourceFieldValue['targetEntity'];
            $value = $getValueCallback($sourceEntity, $sourceFieldName, $sourceEntityMeta, $sourceEntityData);

            if ($value?->getId()) {
                $reflectionClass = new \ReflectionClass($sourceEntityData['entity_class']);
                $classShortName = $reflectionClass->getShortName();
                $fieldLabel = class_exists('Oro\Bundle\MakerBundle\Helper\TranslationHelper')
                    ? TranslationHelper::getEntityLabel($sourceEntityData['entity_class'])
                    : $classShortName;
                $fieldsData[$sourceFieldName] = [
                    'entity_class' => $targetClass,
                    'field_name' => UnidirectionalFieldHelper::createUnidirectionalField(
                        $targetClass,
                        $fieldLabel
                    ),
                    'entity_ids' => [$value->getId()],
                ];
            }
        }

        return $fieldsData;
    }

    public function processChangedEntities(array $sourceEntityData): array
    {
        return $sourceEntityData;
    }

    public function processInverseRelations(array $sourceEntityData): array
    {
        return [];
    }
}
