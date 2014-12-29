<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_1;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

/**
 * Currently there are extend.is_extend and extend.extend attributes for entity field,
 * but they mean the same: indicate whether a field is extensible or not.
 * Here old extend.is_extend attribute is removed and than rename extend.extend to extend.is_extend.
 * As result only extend.is_extend attribute will exist and its value will be equal to the old extend.extend attribute
 * Also this query fix invalid relation keys: change field type to relation type
 */
class AdjustRelationKeyAndIsExtendForFieldQuery extends ParametrizedMigrationQuery
{
    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /**
     * @param FieldTypeHelper $fieldTypeHelper
     */
    public function __construct(FieldTypeHelper $fieldTypeHelper)
    {
        $this->fieldTypeHelper = $fieldTypeHelper;
    }

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
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $classNames = $this->getAllConfigurableEntities($logger);
        foreach ($classNames as $className) {
            $fieldConfigs        = $this->loadFieldConfigs($logger, $className);
            foreach ($fieldConfigs as $fieldConfig) {
                $data = $fieldConfig['data'];
                if (!isset($data['extend'])) {
                    // skip because this field has no any attributes in 'extend' scope
                    continue;
                }
                if (isset($data['extend']['is_extend']) || array_key_exists('is_extend', $data['extend'])) {
                    // remove old extend.is_extend attribute
                    unset($data['extend']['is_extend']);
                }
                if (isset($data['extend']['extend']) || array_key_exists('extend', $data['extend'])) {
                    // rename extend.extend to extend.is_extend
                    $data['extend']['is_extend'] = $data['extend']['extend'];
                    unset($data['extend']['extend']);
                }
                if (isset($data['extend']['relation_key']) && is_string($data['extend']['relation_key'])) {
                    $parts = explode('|', $data['extend']['relation_key']);
                    if (count($parts) === 4 && $parts[0] !== $this->fieldTypeHelper->getUnderlyingType($parts[0])) {
                        // change field type to relation type in extend.relation_key attribute
                        $parts[0] = $this->fieldTypeHelper->getUnderlyingType($parts[0]);
                        $data['extend']['relation_key'] = implode('|', $parts);
                    }
                }

                $query  = 'UPDATE oro_entity_config_field SET data = :data WHERE id = :id';
                $params = ['data' => $data, 'id' => $fieldConfig['id']];
                $types  = ['data' => 'array', 'id' => 'integer'];
                $this->logQuery($logger, $query, $params, $types);
                if (!$dryRun) {
                    $this->connection->executeUpdate($query, $params, $types);
                }
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return string[]
     */
    protected function getAllConfigurableEntities(LoggerInterface $logger)
    {
        $sql = 'SELECT class_name FROM oro_entity_config';
        $this->logQuery($logger, $sql);

        $result = [];
        $rows   = $this->connection->fetchAll($sql);
        foreach ($rows as $row) {
            $result[] = $row['class_name'];
        }

        return $result;
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $className
     *
     * @return array
     */
    protected function loadFieldConfigs(LoggerInterface $logger, $className)
    {
        $sql    = 'SELECT fc.id, fc.type, fc.field_name, fc.data'
            . ' FROM oro_entity_config ec'
            . ' INNER JOIN oro_entity_config_field fc ON fc.entity_id = ec.id'
            . ' WHERE ec.class_name = :class';
        $params = ['class' => $className];
        $types  = ['class' => 'string'];
        $this->logQuery($logger, $sql, $params, $types);

        $result = [];

        $rows = $this->connection->fetchAll($sql, $params, $types);
        foreach ($rows as $row) {
            $fieldName          = $row['field_name'];
            $result[$fieldName] = [
                'id'   => $row['id'],
                'type' => $row['type'],
                'data' => $this->connection->convertToPHPValue($row['data'], 'array')
            ];
        }

        return $result;
    }
}
