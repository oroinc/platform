<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

abstract class AbstractEntityConfigQuery implements MigrationQuery, ConnectionAwareInterface
{
    /** @var Connection */
    protected $connection;

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return int
     */
    abstract public function getRowBatchLimit();

    /**
     * @param array $row
     *
     * @return void
     */
    abstract public function processRow(array $row);

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $steps = ceil($this->getEntityConfigCount() / $this->getRowBatchLimit());

        $entityConfigQb = $this->createEntityConfigQb()
            ->setMaxResults($this->getRowBatchLimit());

        for ($i = 0; $i < $steps; $i++) {
            $rows = $entityConfigQb
                ->setFirstResult($i * $this->getRowBatchLimit())
                ->execute()
                ->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $this->processRow($row);
            }
        }
    }

    /**
     * @param int    $entityId
     * @param string $fieldName
     *
     * @return array
     */
    protected function getEntityConfigFieldFromDb($entityId, $fieldName)
    {
        $fieldConfigFromDb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('oro_entity_config_field', 'ecf')
            ->where('ecf.entity_id = :entity_id')
            ->andWhere('ecf.field_name = :field_name')
            ->setParameter('entity_id', $entityId)
            ->setParameter('field_name', $fieldName)
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);

        return $fieldConfigFromDb;
    }

    /**
     * @return int
     */
    private function getEntityConfigCount()
    {
        return $this->createEntityConfigQb()
            ->select('COUNT(1)')
            ->execute()
            ->fetchColumn();
    }

    /**
     * @return QueryBuilder
     */
    private function createEntityConfigQb()
    {
        return $this->connection->createQueryBuilder()
            ->select('*')
            ->from('oro_entity_config', 'ec');
    }
}
