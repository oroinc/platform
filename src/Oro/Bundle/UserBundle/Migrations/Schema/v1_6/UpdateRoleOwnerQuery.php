<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_6;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateRoleOwnerQuery extends ParametrizedMigrationQuery
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
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $className = 'Oro\Bundle\UserBundle\Entity\Role';
        $classConfig = $this->loadEntityConfigData($logger, $className);
        $data = unserialize($classConfig['data']);

        unset($data['ownership']);

        $query  = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
        $params = ['data' => $data, 'id' => $classConfig['id']];
        $types  = ['data' => 'array', 'id' => 'integer'];
        $this->logQuery($logger, $query, $params, $types);
        if (!$dryRun) {
            $this->connection->executeUpdate($query, $params, $types);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $className
     *
     * @return array
     */
    protected function loadEntityConfigData(LoggerInterface $logger, $className)
    {
        $sql = 'SELECT ec.id, ec.data'
            . ' FROM oro_entity_config ec'
            . ' WHERE ec.class_name = :class';
        $params = ['class' => $className];
        $types  = ['class' => 'string'];
        $this->logQuery($logger, $sql, $params, $types);

        $rows = $this->connection->fetchAll($sql, $params, $types);

        return $rows[0];
    }
}
