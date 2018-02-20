<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateOwnershipTypeQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @var array
     */
    protected $ownershipData;

    /**
     * @param string $className
     * @param array  $ownershipData
     */
    public function __construct($className, $ownershipData)
    {
        $this->className = $className;
        $this->ownershipData = $ownershipData;
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
        $classConfig = $this->loadEntityConfigData($logger, $this->className);
        if ($classConfig) {
            $data = $this->connection->convertToPHPValue($classConfig['data'], 'array');

            $data = $this->getNewData($data);

            $query  = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
            $params = ['data' => $data, 'id' => $classConfig['id']];
            $types  = ['data' => 'array', 'id' => 'integer'];
            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeUpdate($query, $params, $types);
            }
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

        return isset($rows[0]) ? $rows[0] : false;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getNewData($data)
    {
        $data['ownership'] = (isset($data['ownership'])) ?
            array_merge($data['ownership'], $this->ownershipData) :
            $this->ownershipData;

        return $data;
    }
}
