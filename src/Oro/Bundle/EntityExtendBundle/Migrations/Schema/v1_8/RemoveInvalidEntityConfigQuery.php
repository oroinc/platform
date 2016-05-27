<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class RemoveInvalidEntityConfigQuery implements MigrationQuery, ConnectionAwareInterface
{
    const LIMIT = 100;

    /** @var Connection */
    protected $connection;

    /** @var array */
    protected $invalidExtendConfigs = [
        'scale'     => null,
        'length'    => null,
        'precision' => null,
    ];

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
        return 'Removes invalid configs from entity configs';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $steps = ceil($this->getEntityConfigsCount() / static::LIMIT);

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
    protected function processRow(array $row)
    {
        $convertedData = Type::getType(Type::TARRAY)
            ->convertToPHPValue($row['data'], $this->connection->getDatabasePlatform());

        $requiresUpdate = false;
        if ($updatedExtendConfig = $this->updateExtendConfig($convertedData)) {
            $convertedData = $updatedExtendConfig;
            $requiresUpdate = true;
        }
        if ($updatedPendingchanges = $this->updatePendingChanges($convertedData)) {
            $convertedData = $updatedPendingchanges;
            $requiresUpdate = true;
        }

        if (!$requiresUpdate) {
            return;
        }

        $this->connection->update(
            'oro_entity_config',
            ['data' => $convertedData],
            ['id' => $row['id']],
            [Type::TARRAY]
        );
    }

    /**
     * @param array $config
     *
     * @return array|boolean Config or false if there is no update
     */
    protected function updateExtendConfig(array $config)
    {
        $extendConfig = $config['extend'];
        $validExtendConfig = array_diff_key($extendConfig, $this->invalidExtendConfigs);
        if (count($extendConfig) === count($validExtendConfig)) {
            return false;
        }

        $config['extend'] = $validExtendConfig;

        return $config;
    }

    /**
     * @param array $config
     *
     * @return array|boolean Config or false if there is no update
     */
    protected function updatePendingChanges(array $config)
    {
        if (!isset($config['extend']['pending_changes']['extend'])) {
            return false;
        }

        $extendPendingChanges = $config['extend']['pending_changes']['extend'];
        $validExtendPendingChanges = array_diff_key($extendPendingChanges, $this->invalidExtendConfigs);
        if (count($extendPendingChanges) === count($validExtendPendingChanges)) {
            return false;
        }

        $config['extend']['pending_changes']['extend'] = $validExtendPendingChanges;

        return $config;
    }

    /**
     * @return int
     */
    protected function getEntityConfigsCount()
    {
        return $this->createEntityConfigQb()
            ->select('COUNT(1)')
            ->execute()
            ->fetchColumn();
    }

    /**
     * @return QueryBuilder
     */
    protected function createEntityConfigQb()
    {
        return $this->connection->createQueryBuilder()
            ->select('ec.id, ec.data')
            ->from('oro_entity_config', 'ec');
    }
}
