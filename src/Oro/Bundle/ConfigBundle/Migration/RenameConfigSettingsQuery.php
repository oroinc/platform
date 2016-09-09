<?php

namespace Oro\Bundle\ConfigBundle\Migration;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class RenameConfigSettingsQuery extends ParametrizedMigrationQuery
{
    /** @var string */
    private $oldName;

    /** @var string */
    private $newName;

    /**
     * @param string $oldName
     * @param string $newName
     */
    public function __construct($oldName, $newName)
    {
        $this->oldName = $oldName;
        $this->newName = $newName;
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
     * @param bool $dryRun
     */
    protected function processQueries(LoggerInterface $logger, $dryRun = false)
    {
        $query = 'UPDATE oro_config_value SET name = ? WHERE name = ?';
        $parameters = [$this->newName, $this->oldName];

        $this->logQuery($logger, $query, $parameters);
        if (!$dryRun) {
            $this->connection->executeUpdate($query, $parameters);
        }
    }
}
