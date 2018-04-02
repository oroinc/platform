<?php

namespace Oro\Bundle\ConfigBundle\Migration;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class RenameConfigSectionQuery extends ParametrizedMigrationQuery
{
    /** @var string */
    private $oldSection;

    /** @var string */
    private $newSection;

    /**
     * @param string $oldSection
     * @param string $newSection
     */
    public function __construct($oldSection, $newSection)
    {
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
     * @param bool $dryRun
     */
    protected function processQueries(LoggerInterface $logger, $dryRun = false)
    {
        $query = 'UPDATE oro_config_value SET section = ? WHERE section = ?';
        $parameters = [$this->newSection, $this->oldSection];

        $this->logQuery($logger, $query, $parameters);
        if (!$dryRun) {
            $this->connection->executeUpdate($query, $parameters);
        }
    }
}
