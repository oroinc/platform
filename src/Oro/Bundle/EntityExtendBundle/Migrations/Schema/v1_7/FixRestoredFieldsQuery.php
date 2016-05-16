<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_7;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class FixRestoredFieldsQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $configs = $this->loadConfigs($logger);
        foreach ($configs as $entityId => $entityConfig) {
            $changedFields = [];
            foreach ($entityConfig['fields'] as $fieldId => $fieldConfig) {
                $fieldData = $fieldConfig['data'];
                if (!$this->isRestoredField($fieldData)) {
                    continue;
                }

                $fieldData['extend']['is_deleted'] = false;

                $query  = 'UPDATE oro_entity_config_field SET data = :data WHERE id = :id';
                $params = ['data' => $fieldData, 'id' => $fieldId];
                $types  = ['data' => 'array', 'id' => 'integer'];
                $this->logQuery($logger, $query, $params, $types);
                if (!$dryRun) {
                    $this->connection->executeUpdate($query, $params, $types);
                }

                $changedFields[] = $fieldConfig['name'];
            }

            if (!empty($changedFields)) {
                $entityData = $entityConfig['data'];
                $hasChanges = false;
                foreach ($changedFields as $fieldName) {
                    if ($this->isPrivateField($fieldName, $entityData)) {
                        unset($entityData['extend']['schema']['property'][$fieldName]['private']);
                        $hasChanges = true;
                    }
                }
                if ($hasChanges) {
                    $query  = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
                    $params = ['data' => $entityData, 'id' => $entityId];
                    $types  = ['data' => 'array', 'id' => 'integer'];
                    $this->logQuery($logger, $query, $params, $types);
                    if (!$dryRun) {
                        $this->connection->executeUpdate($query, $params, $types);
                    }
                }
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     *  [
     *      entity id => [
     *          'data' => entity data,
     *          'fields' => [
     *              field id => [
     *                  'data' => field data,
     *                  'name' => field name
     *              ],
     *              ...
     *          ]
     *      ],
     *      ...
     *  ]
     */
    protected function loadConfigs(LoggerInterface $logger)
    {
        $entitySql = 'SELECT id, data FROM oro_entity_config';
        $fieldSql = 'SELECT entity_id, id, field_name as name, data FROM oro_entity_config_field';

        $this->logQuery($logger, $entitySql);
        $entityRows = $this->connection->fetchAll($entitySql);

        $this->logQuery($logger, $fieldSql);
        $fieldRows = $this->connection->fetchAll($fieldSql);

        $result = [];
        foreach ($entityRows as $row) {
            $entityData = $this->connection->convertToPHPValue($row['data'], 'array');
            if (!$this->isDeletedEntity($entityData)) {
                $result[$row['id']] = ['data' => $entityData, 'fields' => []];
            }
        }
        foreach ($fieldRows as $row) {
            $entityId = $row['entity_id'];
            if (isset($result[$entityId])) {
                $result[$entityId]['fields'][$row['id']] = [
                    'data' => $this->connection->convertToPHPValue($row['data'], 'array'),
                    'name' => $row['name']
                ];
            }
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function isDeletedEntity(array $data)
    {
        return
            isset($data['extend']['is_deleted'])
            && $data['extend']['is_deleted'];
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function isRestoredField(array $data)
    {
        return
            isset($data['extend']['state'])
            && $data['extend']['state'] === ExtendScope::STATE_ACTIVE
            && isset($data['extend']['is_deleted'])
            && $data['extend']['is_deleted'];
    }

    /**
     * @param string $fieldName
     * @param array  $entityData
     *
     * @return bool
     */
    protected function isPrivateField($fieldName, array $entityData)
    {
        return
            isset($entityData['extend']['schema']['property'][$fieldName]['private'])
            && $entityData['extend']['schema']['property'][$fieldName]['private'];
    }
}
