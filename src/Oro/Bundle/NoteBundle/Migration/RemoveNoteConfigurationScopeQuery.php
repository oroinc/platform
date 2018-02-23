<?php

namespace Oro\Bundle\NoteBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class RemoveNoteConfigurationScopeQuery extends ParametrizedMigrationQuery
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info(
            sprintf(
                'Remove outdated entity configuration data with scope name "%s".',
                'note'
            )
        );
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
     * @param boolean $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $sql = 'SELECT id, class_name, data FROM oro_entity_config';
        $this->logQuery($logger, $sql);

        $entityConfigs = $this->connection->fetchAll($sql);
        $entityConfigs = array_map(function ($entityConfig) {
            $entityConfig['data'] = empty($entityConfig['data'])
                ? []
                : $this->connection->convertToPHPValue($entityConfig['data'], Type::TARRAY);

            return $entityConfig;
        }, $entityConfigs);

        foreach ($entityConfigs as $entityConfig) {
            unset($entityConfig['data']['note']);
            $sql = 'UPDATE oro_entity_config SET data = ? WHERE id = ?';
            $parameters = [
                $this->connection->convertToDatabaseValue($entityConfig['data'], Type::TARRAY),
                $entityConfig['id']
            ];

            $this->logQuery($logger, $sql, $parameters);

            if (!$dryRun) {
                $this->connection->executeUpdate($sql, $parameters);
            }
        }
    }
}
