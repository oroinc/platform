<?php

namespace Oro\Bundle\LocaleBundle\Strategy\Processor;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Storage\EntityFallbackFieldsStorage;

/**
 * For entity to find relationship with edited parent entity.
 * LocalizedFallbackValue will find from a map of all entity extends AbstractLocalizedFallbackValue
 */
class LocalizedFallbackValueAuditStrategyProcessor implements EntityAuditStrategyProcessorInterface
{
    protected ManagerRegistry $doctrine;

    protected EntityFallbackFieldsStorage $storage;

    public function __construct(
        ManagerRegistry $doctrine,
        EntityFallbackFieldsStorage $storage
    ) {
        $this->doctrine = $doctrine;
        $this->storage = $storage;
    }

    public function processInverseCollections(array $sourceEntityData): array
    {
        $fieldData = [];
        $sourceEntityId = $sourceEntityData['entity_id'];
        $sourceEntityClass = $sourceEntityData['entity_class'];
        $sourceEntityManager = $this->doctrine->getManagerForClass($sourceEntityClass);
        $sourceEntity = $sourceEntityManager->find($sourceEntityClass, $sourceEntityId);

        if ($sourceEntity) {
            $fieldData = $this->processLocalizedFallbackValueTargetField($sourceEntity);
        }

        return $fieldData;
    }

    /**
     * @param LocalizedFallbackValue $sourceEntity
     * @return array|null
     */
    private function processLocalizedFallbackValueTargetField(LocalizedFallbackValue $sourceEntity): ?array
    {
        $fieldMap = $this->storage->getFieldMap();
        $fieldsData = [];

        foreach ($fieldMap as $className => $fields) {
            $entityMeta = $this->doctrine->getManagerForClass($className)->getClassMetadata($className);
            $fallbackRepository = $this->doctrine->getRepository(LocalizedFallbackValue::class);

            foreach ($fields as $field) {
                $targetEntityClass = $entityMeta->associationMappings[$field]['targetEntity'] ?? "";

                if (!$targetEntityClass || !is_a($sourceEntity, $targetEntityClass)) {
                    continue;
                }

                $resultId = $fallbackRepository->getParentIdByFallbackValue($className, $field, $sourceEntity);

                if (!is_null($resultId)) {
                    // key '_assoc' doesn't matter, it will not be used in CollectionsChunkProcessor really.
                    $fieldsData['_assoc'] = [
                        'entity_class' => $className,
                        'field_name' => $field,
                        'entity_ids' => [$resultId],
                    ];

                    break 2;
                }
            }
        }

        return $fieldsData;
    }

    public function processChangedEntities(array $sourceEntityData): array
    {
        return [];
    }

    public function processInverseRelations(array $sourceEntityData): array
    {
        return [];
    }
}
