<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_13;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\UserBundle\Entity\User;
use Psr\Log\LoggerInterface;

class RemoveDictionaryGroupForUserQuery extends ParametrizedMigrationQuery
{
    #[\Override]
    public function getDescription(): string|array
    {
        return 'Remove dictionary group for User entity';
    }

    #[\Override]
    public function execute(LoggerInterface $logger): void
    {
        $sql = 'SELECT id, data FROM oro_entity_config WHERE class_name = ? LIMIT 1';
        $parameters = [User::class];
        $row = $this->connection->fetchAssociative($sql, $parameters);
        $this->logQuery($logger, $sql, $parameters);
        $data = $this->connection->convertToPHPValue($row['data'], Types::ARRAY);
        if (isset($data['grouping']['groups']) && \in_array('dictionary', $data['grouping']['groups'], true)) {
            unset($data['grouping']['groups'][array_search('dictionary', $data['grouping']['groups'], true)]);
            if (!$data['grouping']['groups']) {
                unset($data['grouping']['groups']);
            }
            if (!$data['grouping']) {
                unset($data['grouping']);
            }
        }
        if (isset($data['dictionary'])) {
            unset($data['dictionary']);
        }
        $sql = 'UPDATE oro_entity_config SET data = ? WHERE id = ?';
        $parameters = [$this->connection->convertToDatabaseValue($data, Types::ARRAY), $row['id']];
        $statement = $this->connection->prepare($sql);
        $statement->executeQuery($parameters);
        $this->logQuery($logger, $sql, $parameters);
    }
}
