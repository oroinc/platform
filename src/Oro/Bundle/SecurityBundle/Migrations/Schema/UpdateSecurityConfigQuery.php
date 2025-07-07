<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Query should be used when entity security config should be changed
 */
class UpdateSecurityConfigQuery extends ParametrizedMigrationQuery
{
    public function __construct(
        protected string $className,
        protected array $securityData
    ) {
    }

    #[\Override]
    public function getDescription(): array
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger): void
    {
        $this->doExecute($logger);
    }

    public function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        $classConfig = $this->loadEntityConfigData($logger, $this->className);
        if ($classConfig) {
            $data = $this->connection->convertToPHPValue($classConfig['data'], 'array');

            $data = $this->getNewData($data);

            $query  = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
            $params = ['data' => $data, 'id' => $classConfig['id']];
            $types  = ['data' => 'array', 'id' => 'integer'];
            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeStatement($query, $params, $types);
            }
        }
    }

    private function loadEntityConfigData(LoggerInterface $logger, string $className): bool|array
    {
        $sql = 'SELECT ec.id, ec.data'
            . ' FROM oro_entity_config ec'
            . ' WHERE ec.class_name = :class';
        $params = ['class' => $className];
        $types  = ['class' => 'string'];
        $this->logQuery($logger, $sql, $params, $types);

        $rows = $this->connection->fetchAllAssociative($sql, $params, $types);

        return $rows[0] ?? false;
    }

    private function getNewData(array $data): array
    {
        $data['security'] = (isset($data['security'])) ?
            array_merge($data['security'], $this->securityData) :
            $this->securityData;

        return $data;
    }
}
