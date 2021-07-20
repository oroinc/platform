<?php

namespace Oro\Bundle\ConfigBundle\Migration;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Renames the name and optionally section for a configuration option.
 */
class RenameConfigNameQuery extends ParametrizedMigrationQuery
{
    /** @var string */
    private $oldName;

    /** @var string */
    private $newName;

    /** @var string */
    private $oldSection;

    /** @var string|null */
    private $newSection;

    public function __construct(string $oldName, string $newName, string $oldSection, string $newSection = null)
    {
        $this->oldName = $oldName;
        $this->newName = $newName;
        $this->oldSection = $oldSection;
        $this->newSection = $newSection;
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
        if ($this->newSection && $this->newSection !== $this->oldSection) {
            $query = 'UPDATE oro_config_value SET section = :new_section, name = :new_name'
                . ' WHERE section = :old_section AND name = :old_name';
            $parameters = [
                'new_section' => $this->newSection,
                'new_name'    => $this->newName,
                'old_section' => $this->oldSection,
                'old_name'    => $this->oldName
            ];
            $types = [
                'new_section' => Types::STRING,
                'new_name'    => Types::STRING,
                'old_section' => Types::STRING,
                'old_name'    => Types::STRING
            ];
        } else {
            $query = 'UPDATE oro_config_value SET name = :new_name WHERE section = :old_section AND name = :old_name';
            $parameters = [
                'new_name'    => $this->newName,
                'old_section' => $this->oldSection,
                'old_name'    => $this->oldName
            ];
            $types = [
                'new_name'    => Types::STRING,
                'old_section' => Types::STRING,
                'old_name'    => Types::STRING
            ];
        }

        $this->logQuery($logger, $query, $parameters, $types);
        if (!$dryRun) {
            $this->connection->executeStatement($query, $parameters, $types);
        }
    }
}
