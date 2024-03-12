<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\V1_17;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Psr\Log\LoggerInterface;

class UpdateDataActivityScopeShowOnPageValues extends ParametrizedMigrationQuery implements Migration
{
    private const FIELD_ID = 'id';
    private const FIELD_DATA = 'data';
    private const TABLE_NAME = 'oro_entity_config';

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
        $sql = 'SELECT ' . self::FIELD_ID . ', ' . self::FIELD_DATA
            . ' FROM ' . self::TABLE_NAME
            . ' ORDER BY ' . self::FIELD_ID . ' ASC';
        $this->logQuery($logger, $sql);

        $configValues = $this->connection->fetchAllAssociative($sql);

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
            $oldValue = $data[ActivityScope::GROUP_ACTIVITY][ActivityScope::SHOW_ON_PAGE];
            $newValue = constant($oldValue);
            $data[ActivityScope::GROUP_ACTIVITY][ActivityScope::SHOW_ON_PAGE] = $newValue;

            $this->saveConfigData($logger, $id, $data, $dryRun);
        }
    }

    protected function saveConfigData(LoggerInterface $logger, int $id, array $data, $dryRun = false): void
    {
        $sql = 'UPDATE ' . self::TABLE_NAME . ' SET data = :data WHERE id = :id';
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
