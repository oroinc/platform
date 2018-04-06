<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

abstract class AbstractCleanupMarketingMigrationQuery extends ParametrizedMigrationQuery
{
    /**
     * @return array
     */
    abstract public function getClassNames();

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
        foreach ($this->getClassNames() as $className) {
            $options = [
                'className' => $className,
                'logger' => $logger,
                'dryRun' => $dryRun,
            ];

            $this->deleteEntityConfigLog($options);
            $this->deleteEntityConfigLogDiff($options);
            $this->deleteEntityConfigIndexValue($options);
            $this->deleteEntityConfigField($options);
            $this->deleteEntityConfig($options);
        }
    }

    /**
     * @param array $options
     */
    protected function deleteEntityConfigLog($options)
    {
        $query = "DELETE FROM oro_entity_config_log
                  WHERE id IN (
                      SELECT log_id
                      FROM oro_entity_config_log_diff
                      WHERE class_name = :className
                  );";

        $this->executeQuery(
            $query,
            $options['className'],
            $options['logger'],
            $options['dryRun']
        );
    }

    /**
     * @param array $options
     */
    protected function deleteEntityConfigLogDiff($options)
    {
        $query = "DELETE FROM oro_entity_config_log_diff
                  WHERE class_name = :className;";

        $this->executeQuery(
            $query,
            $options['className'],
            $options['logger'],
            $options['dryRun']
        );
    }

    /**
     * @param array $options
     */
    protected function deleteEntityConfigIndexValue($options)
    {
        $query = "DELETE FROM oro_entity_config_index_value
                  WHERE entity_id = (
                      SELECT id
                      FROM oro_entity_config
                      WHERE class_name = :className
                  );";

        $this->executeQuery(
            $query,
            $options['className'],
            $options['logger'],
            $options['dryRun']
        );
    }

    /**
     * @param array $options
     */
    protected function deleteEntityConfigField($options)
    {
        $query = "DELETE FROM oro_entity_config_field
                  WHERE entity_id = (
                      SELECT id
                      FROM oro_entity_config
                      WHERE class_name = :className
                  );";

        $this->executeQuery(
            $query,
            $options['className'],
            $options['logger'],
            $options['dryRun']
        );
    }

    /**
     * @param array $options
     */
    protected function deleteEntityConfig($options)
    {
        $query = "DELETE FROM oro_entity_config
                  WHERE class_name = :className;";

        $this->executeQuery(
            $query,
            $options['className'],
            $options['logger'],
            $options['dryRun']
        );
    }

    /**
     * @param string $query
     * @param string $className
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function executeQuery($query, $className, LoggerInterface $logger, $dryRun = false)
    {
        $parameters = ['className' => $className];
        $this->logQuery($logger, $query, $parameters);

        if (!$dryRun) {
            $this->connection->executeUpdate(
                $query,
                $parameters,
                ['className' => Type::STRING]
            );
        }
    }
}
