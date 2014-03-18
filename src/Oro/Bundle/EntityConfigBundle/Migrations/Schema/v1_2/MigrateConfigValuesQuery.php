<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_2;

use Psr\Log\LoggerInterface;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class MigrateConfigValuesQuery extends ParametrizedMigrationQuery
{
    /**
     * {inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->migrateConfigs($logger, true);

        return $logger->getMessages();
    }

    /**
     * {inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->migrateConfigs($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function migrateConfigs(LoggerInterface $logger, $dryRun = false)
    {
        $configs = $this->loadConfigs($logger);
        foreach ($configs as $key => $value) {
            $tableName = strpos($key, '#') === 0
                ? 'oro_entity_config'
                : 'oro_entity_config_field';
            $id        = strpos($key, '#') === 0
                ? (int)substr($key, 1)
                : (int)$key;

            $query  = sprintf('UPDATE %s SET data = :values WHERE id = :id', $tableName);
            $params = ['values' => $value, 'id' => $id];
            $types  = ['values' => 'array', 'id' => 'integer'];
            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeQuery($query, $params, $types);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return array key = {field id} or #{entity id}
     */
    protected function loadConfigs(LoggerInterface $logger)
    {
        $sql = 'SELECT entity_id, field_id, scope, code, value, serializable'
            . ' FROM oro_entity_config_value'
            . ' ORDER BY entity_id, field_id, scope, code';
        $logger->notice($sql);
        $configValues = $this->connection->fetchAll($sql);
        $configs = [];
        foreach ($configValues as $configValue) {
            $key = $configValue['entity_id'] ? '#' . $configValue['entity_id'] : $configValue['field_id'];
            if (!isset($configs[$key])) {
                $configs[$key] = [];
            }
            $scope = $configValue['scope'];
            $code  = $configValue['code'];
            $value = $configValue['value'];
            if ($configValue['serializable']) {
                $value = unserialize($value);
            }
            if (!isset($configs[$key][$scope])) {
                $configs[$key][$scope] = [];
            }
            $configs[$key][$scope][$code] = $value;
        }

        return $configs;
    }
}
