<?php

namespace Oro\Bundle\ConfigBundle\Migration;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Renames the section for configuration options.
 */
class RenameConfigSectionQuery extends ParametrizedMigrationQuery
{
    /** @var string */
    private $oldSection;

    /** @var string */
    private $newSection;

    /** @var string|null */
    private $name;

    public function __construct(string $oldSection, string $newSection, string $name = null)
    {
        $this->oldSection = $oldSection;
        $this->newSection = $newSection;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->processQueries($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->processQueries($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    private function processQueries(LoggerInterface $logger, $dryRun = false): void
    {
        if ($this->name) {
            $query = 'UPDATE oro_config_value SET section = :new_section WHERE section = :old_section AND name = :name';
            $parameters = [
                'new_section' => $this->newSection,
                'old_section' => $this->oldSection,
                'name'        => $this->name
            ];
            $types = [
                'new_section' => Types::STRING,
                'old_section' => Types::STRING,
                'name'        => Types::STRING
            ];
        } else {
            $query = 'UPDATE oro_config_value SET section = :new_section WHERE section = :old_section';
            $parameters = ['new_section' => $this->newSection, 'old_section' => $this->oldSection];
            $types = ['new_section' => Types::STRING, 'old_section' => Types::STRING];
        }

        $this->logQuery($logger, $query, $parameters, $types);
        if (!$dryRun) {
            $this->connection->executeStatement($query, $parameters, $types);
        }
    }
}
