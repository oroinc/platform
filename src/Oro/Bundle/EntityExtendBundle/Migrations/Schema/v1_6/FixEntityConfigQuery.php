<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_6;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class FixEntityConfigQuery implements MigrationQuery, ConnectionAwareInterface
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
        return 'Fix config for new entities.';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $steps = ceil($this->getEntityConfigCount() / static::LIMIT);

        $entityConfigQb = $this->createEntityConfigQb()
            ->setMaxResults(static::LIMIT);

        for ($i = 0; $i < $steps; $i++) {
            $rows = $entityConfigQb
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
    private function processRow(array $row)
    {
        $data = Type::getType(Type::TARRAY)
            ->convertToPHPValue($row['data'], $this->connection->getDatabasePlatform());

        if (!isset($data['extend']['state']) || $data['extend']['state'] !== 'New') {
            return;
        }

        $pendingChanges = [];

        if (isset($data['activity']['activities']) && $data['activity']['activities']) {
            $pendingChanges['activity']['activities'] = [
                null,
                $data['activity']['activities']
            ];
            unset($data['activity']['activities']);
        }
        if (isset($data['attachment']['enabled']) && $data['attachment']['enabled']) {
            $pendingChanges['attachment']['enabled'] = [false, 1];
            $data['attachment']['enabled'] = false;
        }
        if (isset($data['comment']['enabled']) && $data['comment']['enabled']) {
            $pendingChanges['comment']['enabled'] = [false, 1];
            $data['comment']['enabled'] = false;
        }
        if (isset($data['note']['enabled']) && $data['note']['enabled']) {
            $pendingChanges['note']['enabled'] = [false, 1];
            $data['note']['enabled'] = false;
        }

        if (!$pendingChanges) {
            return;
        }

        $data['extend']['pending_changes'] = $pendingChanges;

        $this->connection->update('oro_entity_config', ['data' => $data], ['id' => $row['id']], [Type::TARRAY]);
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
            ->select('ec.id, ec.data')
            ->from('oro_entity_config', 'ec');
    }
}
