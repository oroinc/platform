<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Actualize removed or is not actual enum option ids for target entities.
 */
class EntityEnumOptionsActualizer
{
    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private ConfigManager $configManager
    ) {
    }

    public function run(string $enumCode, string $optionId): void
    {
        $actualOptions = $this->doctrineHelper->getEntityRepository(EnumOption::class)
            ->getValues($enumCode);
        $isOptionExists = array_filter($actualOptions, fn ($option) => $option->getId() === $optionId);
        if (!empty($isOptionExists)) {
            return;
        }
        $targetEntities = $this->getTargetEntityData($enumCode);
        foreach ($targetEntities as $entityData) {
            $this->actualilzeForEntityField($optionId, ...$entityData);
        }
    }

    protected function getTargetEntityData(string $enumCode): array
    {
        $resut = [];
        $entityConfigs = $this->configManager->getConfigs('extend', null, true);
        foreach ($entityConfigs as $entityConfig) {
            $className = $entityConfig->getId()->getClassName();
            $fieldConfigs = $this->configManager->getConfigs('enum', $className);
            foreach ($fieldConfigs as $fieldConfig) {
                $fieldEnumCode = $fieldConfig->get('enum_code');
                if (!$fieldEnumCode || $fieldEnumCode !== $enumCode) {
                    continue;
                }
                $fieldName = $fieldConfig->getId()->getFieldName();
                $fieldType = $fieldConfig->getId()->getFieldType();
                $extendFieldConfig = $this->configManager->getFieldConfig('extend', $className, $fieldName);
                if (!$extendFieldConfig->is('is_serialized')) {
                    continue;
                }
                $resut[] = [$className, $fieldName, $fieldType];
            }
        }

        return $resut;
    }

    protected function actualilzeForEntityField(
        string $optionId,
        string $className,
        string $fieldName,
        string $fieldType
    ): void {
        $entityRepository = $this->doctrineHelper->getEntityRepository($className);
        $queryBuilder = $entityRepository
            ->createQueryBuilder('e')
            ->update();

        if (ExtendHelper::isMultiEnumType($fieldType)) {
            $queryBuilder->set(
                'e.serialized_data',
                QueryBuilderUtil::sprintf(
                    "JSONB_SET_WITH_EXTRACT(e.serialized_data, '{%s}', e.serialized_data, '%s',  :optionId)",
                    $fieldName,
                    $fieldName
                )
            )
                ->andWhere(
                    $queryBuilder->expr()->isNotNull(
                        QueryBuilderUtil::sprintf("JSON_EXTRACT(e.serialized_data, '%s')", $fieldName)
                    )
                )
                ->andWhere(
                    QueryBuilderUtil::sprintf(
                        "JSONB_ARRAY_CONTAINS_JSON(e.serialized_data, '%s', '\"%s\"') = true",
                        $fieldName,
                        $optionId
                    )
                )
                ->setParameter('optionId', $optionId);
        } else {
            $queryBuilder->set('e.serialized_data', QueryBuilderUtil::sprintf("e.serialized_data - '%s'", $fieldName))
                ->andWhere(
                    QueryBuilderUtil::sprintf("JSON_EXTRACT(e.serialized_data, '%s') = :enumOptionId", $fieldName)
                )->setParameter('enumOptionId', $optionId);
        }
        $queryBuilder->getQuery()->execute();
    }
}
