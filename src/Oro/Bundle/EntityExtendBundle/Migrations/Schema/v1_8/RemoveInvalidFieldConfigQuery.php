<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class RemoveInvalidFieldConfigQuery implements MigrationQuery, ConnectionAwareInterface
{
    const LIMIT = 100;

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
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Removes invalid configs from field configs';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $steps = ceil($this->getEntityConfigFieldsCount() / static::LIMIT);

        $entityConfigFieldQb = $this->createEntityConfigFieldQb()
            ->setMaxResults(static::LIMIT);

        for ($i = 0; $i < $steps; $i++) {
            $rows = $entityConfigFieldQb
                ->setFirstResult($i * static::LIMIT)
                ->execute()
                ->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $this->processRow($row);
            }
        }
    }

    /**
     * @param array $row
     */
    protected function processRow(array $row)
    {
        $convertedData = Type::getType(Type::TARRAY)
            ->convertToPHPValue($row['data'], $this->connection->getDatabasePlatform());
        if (!isset($convertedData['extend']['pending_changes'])) {
            return;
        }

        unset($convertedData['extend']['pending_changes']);
        $this->connection->update(
            'oro_entity_config_field',
            ['data' => $convertedData],
            ['id' => $row['id']],
            [Type::TARRAY]
        );
    }

    /**
     * @return int
     */
    protected function getEntityConfigFieldsCount()
    {
        return $this->createEntityConfigFieldQb()
            ->select('COUNT(1)')
            ->execute()
            ->fetchColumn();
    }

    /**
     * @return QueryBuilder
     */
    protected function createEntityConfigFieldQb()
    {
        return $this->connection->createQueryBuilder()
            ->select('cf.id, cf.data')
            ->from('oro_entity_config_field', 'cf');
    }
}
