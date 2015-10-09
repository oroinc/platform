<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_2;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateModuleAndEntityFieldsQuery extends ParametrizedMigrationQuery
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
     * {@inheritdoc}
     */
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $getSql = 'SELECT id, class_name FROM oro_entity_config';
        $this->logQuery($logger, $getSql);
        $configs   = $this->connection->fetchAll($getSql);
        $indexes   = $this->getIndexes($logger);
        $insertSql = 'INSERT INTO oro_entity_config_index_value '
            . '(entity_id, scope, code, value) '
            . 'VALUES (:entity_id, :scope, :code, :value)';
        foreach ($configs as $config) {
            $id        = (int)$config['id'];
            $className = $config['class_name'];

            list($moduleName, $entityName) = ConfigHelper::getModuleAndEntityNames($className);

            if (!isset($indexes[$id]['module_name'])) {
                $params = [
                    'entity_id' => $id,
                    'scope'     => 'entity_config',
                    'code'      => 'module_name',
                    'value'     => $moduleName
                ];
                $types  = [
                    'entity_id' => 'integer',
                    'scope'     => 'string',
                    'code'      => 'string',
                    'value'     => 'string'
                ];
                $this->logQuery($logger, $insertSql, $params, $types);
                if (!$dryRun) {
                    $this->connection->executeUpdate($insertSql, $params, $types);
                }
            }
            if (!isset($indexes[$id]['entity_name'])) {
                $params = [
                    'entity_id' => $id,
                    'scope'     => 'entity_config',
                    'code'      => 'entity_name',
                    'value'     => $entityName
                ];
                $types  = [
                    'entity_id' => 'integer',
                    'scope'     => 'string',
                    'code'      => 'string',
                    'value'     => 'string'
                ];
                $this->logQuery($logger, $insertSql, $params, $types);
                if (!$dryRun) {
                    $this->connection->executeUpdate($insertSql, $params, $types);
                }
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function getIndexes(LoggerInterface $logger)
    {
        $sql = 'SELECT entity_id, code '
            . 'FROM oro_entity_config_index_value '
            . 'WHERE entity_id IS NOT NULL AND scope = \'entity_config\' '
            . 'AND code IN (\'module_name\', \'entity_name\')';
        $this->logQuery($logger, $sql);
        $rows = $this->connection->fetchAll($sql);

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['entity_id']][$row['code']] = true;
        }

        return $result;
    }
}
