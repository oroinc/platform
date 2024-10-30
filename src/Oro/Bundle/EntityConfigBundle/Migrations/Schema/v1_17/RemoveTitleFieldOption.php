<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Psr\Log\LoggerInterface;

class RemoveTitleFieldOption extends ParametrizedMigrationQuery implements Migration
{
    private const FIELD_ID = 'id';
    private const FIELD_DATA = 'data';
    private const TABLE_NAME = 'oro_entity_config_field';

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new self());
    }

    #[\Override]
    public function getDescription(): array|string
    {
        $logger = new ArrayLogger();
        $this->execute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger, $dryRun = false)
    {
        $configData = $this->loadConfigData($logger);
        $this->processConfigData($logger, $configData, $dryRun);
    }

    protected function loadConfigData(LoggerInterface $logger): array
    {
        $sql = sprintf(
            'SELECT %s, %s FROM %s WHERE type = :field_type',
            self::FIELD_ID,
            self::FIELD_DATA,
            self::TABLE_NAME
        );

        $params = $types =  ['field_type' => Types::STRING];
        $this->logQuery($logger, $sql);

        $configValues = $this->connection->fetchAllAssociative($sql, $params, $types);

        $configData = [];
        foreach ($configValues as $configValue) {
            $configData[$configValue[self::FIELD_ID]] = $configValue[self::FIELD_DATA];
        }

        return $configData;
    }

    protected function processConfigData(LoggerInterface $logger, array $configData, $dryRun = false): void
    {
        foreach ($configData as $id => $data) {
            $data = $this->connection->convertToPHPValue($data, Types::ARRAY);

            if (isset($data['search']['title_field'])) {
                unset($data['search']['title_field']);
                $this->saveConfigData($logger, $id, $data, $dryRun);
            }
        }
    }

    protected function saveConfigData(LoggerInterface $logger, int $id, array $data, $dryRun = false): void
    {
        $sql = sprintf('UPDATE %s SET data = :data WHERE id = :id', self::TABLE_NAME);
        $parameters = [
            'data' => $data,
            'id' => $id,
        ];
        $types = ['id' => Types::INTEGER, 'data' => Types::ARRAY];

        $this->logQuery($logger, $sql, $parameters, $types);

        if (!$dryRun) {
            $this->connection->executeStatement($sql, $parameters, $types);
        }
    }
}
