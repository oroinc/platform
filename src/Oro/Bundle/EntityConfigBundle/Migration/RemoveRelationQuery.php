<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Psr\Log\LoggerInterface;

abstract class RemoveRelationQuery extends RemoveFieldQuery
{
    /**
     * Returns the type of the relation, e.g. manyToMany, oneToMany, manyToOne
     *
     * @return string
     */
    abstract public function getRelationType();

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Remove config for relation ' . $this->entityField . ' of entity ' . $this->entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $entityRow = $this->getEntityRow($this->entityClass);
        if (!$entityRow) {
            $logger->info("Entity '{$this->entityClass}' not found");

            return;
        }
        $entityData = $this->connection->convertToPHPValue($entityRow['data'], Type::TARRAY);

        $fieldRow = $this->getFieldRow($this->entityClass, $this->entityField);
        if (!$fieldRow) {
            $logger->info("Relation '{$this->entityField}' not found in '{$this->entityClass}'");

            return;
        }
        $fieldData = $this->connection->convertToPHPValue($fieldRow['data'], Type::TARRAY);

        $isSystemRelation = $this->isSystemRelation($fieldData);
        if (!$isSystemRelation && !$this->isOwningSide($entityData, $fieldData)) {
            $logger->info("Removal of relation config is possible only from owning side");

            return;
        }

        // delete owning side field config
        $this->removeFieldConfig($logger, $fieldRow['id']);

        if ($isSystemRelation) {
            // system relations do not need any modifications in entity configuration.

            return;
        }

        $relationKey = $fieldData['extend']['relation_key'];

        // update owning side entity config
        $this->updateEntityData(
            $logger,
            $entityData,
            $this->entityClass,
            $this->entityField,
            $relationKey
        );

        $relationTargetFieldId = $entityData['extend']['relation'][$relationKey]['target_field_id'];
        if ($relationTargetFieldId instanceof FieldConfigId) {
            $targetFieldRow = $this->getFieldRow(
                $relationTargetFieldId->getClassName(),
                $relationTargetFieldId->getFieldName()
            );

            // delete target side field config
            if ($targetFieldRow) {
                $this->removeFieldConfig($logger, $targetFieldRow['id']);
            }

            // update target side entity config
            $targetEntityRow = $this->getEntityRow($fieldData['extend']['target_entity']);
            if ($targetEntityRow) {
                $targetEntityData = $this->connection->convertToPHPValue($targetEntityRow['data'], Type::TARRAY);
                $this->updateEntityData(
                    $logger,
                    $targetEntityData,
                    $relationTargetFieldId->getClassName(),
                    $relationTargetFieldId->getFieldName(),
                    $relationKey
                );
            }
        }
    }

    /**
     * @param array $fieldData
     *
     * @return bool
     */
    protected function isSystemRelation($fieldData)
    {
        return $fieldData['extend']['owner'] === ExtendScope::OWNER_SYSTEM;
    }

    /**
     * @param array $entityData
     * @param array $fieldData
     *
     * @return bool
     */
    protected function isOwningSide($entityData, $fieldData)
    {
        $relationKey = $fieldData['extend']['relation_key'];
        $isOwningSide = $entityData['extend']['relation'][$relationKey]['owner'];

        return $isOwningSide || $this->getRelationType() === RelationType::ONE_TO_MANY;
    }

    /**
     * @param string $entityClass
     *
     * @return array
     */
    protected function getEntityRow($entityClass)
    {
        $getEntitySql = 'SELECT e.data 
                FROM oro_entity_config as e 
                WHERE e.class_name = ? 
                LIMIT 1';

        return $this->connection->fetchAssoc(
            $getEntitySql,
            [$entityClass]
        );
    }

    /**
     * @param LoggerInterface $logger
     * @param array           $entityData
     * @param string          $entityClass
     * @param string          $fieldName
     * @param string          $relationKey
     *
     * @throws DBALException
     */
    protected function updateEntityData(
        LoggerInterface $logger,
        $entityData,
        $entityClass,
        $fieldName,
        $relationKey
    ) {
        if (isset($entityData['extend']['relation'][$relationKey])) {
            unset($entityData['extend']['relation'][$relationKey]);
        }
        if (isset($entityData['extend']['schema']['relation'][$fieldName])) {
            unset($entityData['extend']['schema']['relation'][$fieldName]);
        }
        if (isset($entityData['extend']['schema']['addremove'][$fieldName])) {
            unset($entityData['extend']['schema']['addremove'][$fieldName]);
        }

        $defaultRelationFieldName = ExtendConfigDumper::DEFAULT_PREFIX . $fieldName;
        if (isset($entityData['extend']['schema']['default'][$defaultRelationFieldName])) {
            unset($entityData['extend']['schema']['default'][$defaultRelationFieldName]);
        }

        $data = $this->connection->convertToDatabaseValue($entityData, Type::TARRAY);

        $this->executeQuery(
            $logger,
            'UPDATE oro_entity_config SET data = ? WHERE class_name = ?',
            [
                $data,
                $entityClass
            ]
        );
    }
}
