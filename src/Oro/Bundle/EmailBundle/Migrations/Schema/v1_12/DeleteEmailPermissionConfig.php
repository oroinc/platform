<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class DeleteEmailPermissionConfig extends ParametrizedMigrationQuery
{
    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $sql =
            "DELETE FROM oro_entity_config_index_value
            WHERE
                entity_id = (SELECT id FROM oro_entity_config WHERE class_name = ? LIMIT 1) AND
                field_id IS NULL AND
                scope = ? AND
                code = ?
            ";
        $className = 'Oro\Bundle\EmailBundle\Entity\Email';
        $scope = 'security';
        $parameters = [
            $className,
            $scope,
            'permissions'
        ];
        $statement = $this->connection->prepare($sql);
        if (!$dryRun) {
            $statement->executeQuery($parameters);
        }
        $this->logQuery($logger, $sql, $parameters);

        // update entity config cached data
        $sql = 'SELECT data FROM oro_entity_config WHERE class_name = ? LIMIT 1';
        $parameters = [$className];
        $data = $this->connection->fetchOne($sql, $parameters);
        $this->logQuery($logger, $sql, $parameters);

        $data = $data ? $this->connection->convertToPHPValue($data, Types::ARRAY) : [];
        if (array_key_exists($scope, $data)) {
            unset($data[$scope]);
        }
        $data = $this->connection->convertToDatabaseValue($data, Types::ARRAY);

        $sql = 'UPDATE oro_entity_config SET data = ? WHERE class_name = ?';
        $parameters = [$data, $className];
        $statement = $this->connection->prepare($sql);
        if (!$dryRun) {
            $statement->executeQuery($parameters);
        }
        $this->logQuery($logger, $sql, $parameters);
    }
}
