<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Schema\v1_2;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Query\AbstractEntityConfigQuery;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Psr\Log\LoggerInterface;

class UpdateLocalizedFallbackValueRelationsQuery extends AbstractEntityConfigQuery
{
    const LIMIT = 100;

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Update all LocalizedFallbackValue relations to be unidirectional';
    }

    /**
     * {@inheritdoc}
     */
    public function getRowBatchLimit()
    {
        return static::LIMIT;
    }

    /**
     * {@inheritdoc}
     */
    public function processRow(array $row, LoggerInterface $logger)
    {
        $data = $this->connection->convertToPHPValue($row['data'], 'array');

        // process only extended entities with relations
        if (!$data['extend']['is_extend'] || !isset($data['extend']['relation'])) {
            return;
        }

        foreach ($data['extend']['relation'] as $relation) {

            /** @var FieldConfigId $fieldConfig */
            $fieldConfig = $relation['field_id'];
            if (!$fieldConfig) {
                continue;
            }

            if ($relation['target_entity'] !== LocalizedFallbackValue::class) {
                continue;
            }

            $fieldConfigFromDb = $this->getEntityConfigFieldFromDb($row['id'], $fieldConfig->getFieldName());
            $fieldData = $this->connection->convertToPHPValue($fieldConfigFromDb['data'], 'array');

            // relations with LocalizedFallbackValue should have OWNER_CUSTOM set
            if ($fieldData['extend']['owner'] !== ExtendScope::OWNER_CUSTOM) {
                $query = new UpdateEntityConfigFieldValueQuery(
                    $fieldConfig->getClassName(),
                    $fieldConfig->getFieldName(),
                    'extend',
                    'owner',
                    ExtendScope::OWNER_CUSTOM
                );

                $query->setConnection($this->connection);
                $query->execute($logger);
            }

            // update entity fields config
            $query = new UpdateEntityConfigFieldValueQuery(
                $fieldConfig->getClassName(),
                $fieldConfig->getFieldName(),
                'extend',
                'bidirectional',
                false
            );

            $query->setConnection($this->connection);
            $query->execute($logger);

            // update entity config
            $this->removeInverseSideOfRelation($logger, $relation, $fieldData);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param array           $relationData
     * @param array           $fieldData
     * @return string
     */
    private function removeInverseSideOfRelation(LoggerInterface $logger, array $relationData, array $fieldData)
    {
        $classConfig = $this->getEntityConfigFromDb($relationData['target_entity']);
        $classData = $this->connection->convertToPHPValue($classConfig['data'], 'array');
        $relationKey = $fieldData['extend']['relation_key'];

        if (!isset($classData['extend']['relation'][$relationKey])) {
            return;
        }

        /** @var FieldConfigId $field */
        $field = $classData['extend']['relation'][$relationKey]['field_id'];
        $fieldName = $field->getFieldName();
        unset(
            $classData['extend']['relation'][$relationKey],
            $classData['extend']['schema']['relation'][$fieldName],
            $classData['extend']['schema']['addremove'][$fieldName]
        );

        $this->updateEntityConfigData($classData, $classConfig['id'], $logger);

        // owning class
        /** @var FieldConfigId $targetField */
        $targetField = $relationData['field_id'];
        $targetFieldName = $targetField->getFieldName();

        $owningClassConfig = $this->getEntityConfigFromDb($targetField->getClassName());
        $owningClassData = $this->connection->convertToPHPValue($owningClassConfig['data'], 'array');

        $targetRelationKey = ExtendHelper::toggleRelationKey($relationKey);
        if (isset($owningClassData['extend']['relation'][$targetRelationKey])) {
            $owningClassData['extend']['relation'][$targetRelationKey]['target_field_id'] = false;
            unset(
                $owningClassData['extend']['schema']['addremove'][$targetFieldName]['target'],
                $owningClassData['extend']['schema']['addremove'][$targetFieldName]['is_target_addremove']
            );

            $this->updateEntityConfigData($owningClassData, $owningClassConfig['id'], $logger);
        }
    }
}
