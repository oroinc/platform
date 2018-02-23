<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class RemoveTableQuery extends ParametrizedMigrationQuery
{
    /** @var string  */
    protected $entityClass;

    /**
     * @param string $entityClass
     */
    public function __construct($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $sql = 'SELECT id FROM oro_entity_config WHERE class_name = ? LIMIT 1';

        $fieldRow = $this->connection->fetchAssoc($sql, [$this->entityClass], [Type::STRING]);
        if ($fieldRow) {
            $this->executeQuery($logger, 'DELETE FROM oro_entity_config_field WHERE entity_id = ?', [$fieldRow['id']]);
            $this->executeQuery($logger, 'DELETE FROM oro_entity_config WHERE id = ?', [$fieldRow['id']]);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param string $sql
     * @param array $parameters
     */
    protected function executeQuery(LoggerInterface $logger, $sql, array $parameters = [])
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $this->logQuery($logger, $sql, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Remove config of entity' . $this->entityClass;
    }
}
