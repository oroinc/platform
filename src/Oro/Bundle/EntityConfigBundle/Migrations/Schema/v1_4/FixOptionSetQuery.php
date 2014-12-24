<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class FixOptionSetQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Remove serialized objects from option set configuration. Should be executed in prod environment.';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $query = 'SELECT * FROM oro_entity_config_field WHERE type = ?';
        $params = ['optionSet'];

        $this->logQuery($logger, $query, $params);
        $fields = $this->connection->fetchAll($query, $params);

        $type = Type::getType(Type::TARRAY);
        $platform = $this->connection->getDatabasePlatform();

        foreach ($fields as $field) {
            // sleep and wakeup methods will clear all invalid data
            $data = $type->convertToPHPValue($field['data'], $platform);
            $data = $type->convertToDatabaseValue($data, $platform);

            $query = 'UPDATE oro_entity_config_field SET data = ? WHERE id = ?';
            $params = [$data, $field['id']];

            $this->logQuery($logger, $query, $params);
            $this->connection->executeQuery($query, $params);
        }
    }
}
