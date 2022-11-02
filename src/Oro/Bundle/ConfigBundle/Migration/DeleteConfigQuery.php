<?php

namespace Oro\Bundle\ConfigBundle\Migration;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Deletes the configuration option.
 */
class DeleteConfigQuery extends ParametrizedMigrationQuery
{
    private string $name;

    private string $section;

    public function __construct(string $name, string $section)
    {
        $this->name = $name;
        $this->section = $section;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): array
    {
        $logger = new ArrayLogger();
        $this->processQueries($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger): void
    {
        $this->processQueries($logger);
    }

    private function processQueries(LoggerInterface $logger, bool $dryRun = false): void
    {
        $query = 'DELETE FROM oro_config_value WHERE section = :section AND name = :name';
        $parameters = [
            'section' => $this->section,
            'name' => $this->name,
        ];
        $types = [
            'section' => Types::STRING,
            'name' => Types::STRING,
        ];

        $this->logQuery($logger, $query, $parameters, $types);
        if (!$dryRun) {
            $this->connection->executeStatement($query, $parameters, $types);
        }
    }
}
