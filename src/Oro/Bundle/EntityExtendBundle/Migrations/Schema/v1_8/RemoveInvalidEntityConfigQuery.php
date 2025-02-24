<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class RemoveInvalidEntityConfigQuery implements MigrationQuery, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    const LIMIT = 100;

    /** @var array */
    protected $invalidExtendConfigs = [
        'scale'     => null,
        'length'    => null,
        'precision' => null,
    ];

    #[\Override]
    public function getDescription()
    {
        return 'Removes invalid configs from entity configs';
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $steps = ceil($this->getEntityConfigsCount() / static::LIMIT);

        $entityConfigQb = $this->createEntityConfigQb()
            ->setMaxResults(static::LIMIT);

        for ($i = 0; $i < $steps; $i++) {
            $rows = $entityConfigQb
                ->setFirstResult($i * static::LIMIT)
                ->execute()
                ->fetchAllAssociative();

            foreach ($rows as $row) {
                $this->processRow($row);
            }
        }
    }

    protected function processRow(array $row)
    {
        $convertedData = Type::getType(Types::ARRAY)
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
            [Types::ARRAY]
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
            ->fetchOne();
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
