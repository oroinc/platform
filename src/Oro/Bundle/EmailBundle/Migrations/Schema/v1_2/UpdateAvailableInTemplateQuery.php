<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_2;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

/**
 * Sets value of email.available_in_template attribute to TRUE for all fields
 * except a value of this attribute is changed by an user
 */
class UpdateAvailableInTemplateQuery extends ParametrizedMigrationQuery
{
    protected static $allowedTypes = [
        'string'    => true,
        'integer'   => true,
        'smallint'  => true,
        'bigint'    => true,
        'boolean'   => true,
        'decimal'   => true,
        'datetime'  => true,
        'date'      => true,
        'time'      => true,
        'text'      => true,
        'float'     => true,
        'money'     => true,
        'percent'   => true,
        'optionSet' => true,
        'file'      => true,
        'image'     => true,
        'ref-one'   => true,
        'manyToOne' => true,
    ];

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
            $fieldsChangedByUser = $this->loadFieldsChangedByUser($logger, $className);
            foreach ($fieldConfigs as $fieldName => $fieldConfig) {
                if (isset($fieldsChangedByUser[$fieldName])) {
                    continue;
                }
                if (!isset($allowedTypes[$fieldConfig['type']])) {
                    continue;
                }
                $data = unserialize($fieldConfig['data']);
                if (isset($data['email']['available_in_template']) && $data['email']['available_in_template']) {
                    continue;
                }

                if (!isset($data['email'])) {
                    $data['email'] = [];
                }
                $data['email']['available_in_template'] = true;

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
        $params = ['class' => $className, 'scope' => 'email'];
        $types  = ['class' => 'string', 'scope' => 'string'];
        $this->logQuery($logger, $sql, $params, $types);

        $result = [];

        $rows = $this->connection->fetchAll($sql, $params, $types);
        foreach ($rows as $row) {
            $fieldName          = $row['field_name'];
            $result[$fieldName] = [
                'id'   => $row['id'],
                'type' => $row['type'],
                'data' => $row['data']
            ];
        }

        return $result;
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $className
     *
     * @return string[]
     */
    protected function loadFieldsChangedByUser(LoggerInterface $logger, $className)
    {
        $sql    = 'SELECT field_name, diff'
            . ' FROM oro_entity_config_log_diff'
            . ' WHERE class_name = :class AND scope = :scope AND field_name IS NOT NULL';
        $params = ['class' => $className, 'scope' => 'email'];
        $types  = ['class' => 'string', 'scope' => 'string'];
        $this->logQuery($logger, $sql, $params, $types);

        $result = [];
        $rows   = $this->connection->fetchAll($sql, $params, $types);
        foreach ($rows as $row) {
            $diff = unserialize($row['diff']);
            if (isset($diff['available_in_template'])) {
                $fieldName = $row['field_name'];
                if (!isset($result[$fieldName])) {
                    $result[$fieldName] = $fieldName;
                }
            }
        }

        return $result;
    }
}
