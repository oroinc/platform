<?php

namespace Oro\Bundle\NoteBundle\Migration;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class RemoveNoteConfigurationScopeQuery implements MigrationQuery, ConnectionAwareInterface
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
        return 'Remove Note configuration scope from entities config';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $sql = 'SELECT id, class_name, data FROM oro_entity_config';
        $entityConfigs = $this->connection->fetchAll($sql);
        $entityConfigs = array_map(function ($entityConfig) {
            $entityConfig['data'] = empty($entityConfig['data'])
                ? []
                : $this->connection->convertToPHPValue($entityConfig['data'], Type::TARRAY);

            return $entityConfig;
        }, $entityConfigs);

        foreach ($entityConfigs as $entityConfig) {
            unset($entityConfig['data']['note']);
            $this->connection->executeUpdate(
                'UPDATE oro_entity_config SET data=? WHERE id=?',
                [
                    $this->connection->convertToDatabaseValue($entityConfig['data'], Type::TARRAY),
                    $entityConfig['id']
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }
}
