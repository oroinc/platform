<?php

namespace Oro\Bundle\LocaleBundle\Migration;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * This query populates localized fallback value collection based on select query which can be useful when string value
 * is migrated to be used in localized fallback value collection.
 */
class PopulateLocalizedFallbackCollectionMigrationQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    private $selectQuery;

    /**
     * @var string
     */
    private $insertQuery;

    /**
     * @param string $selectQuery query which should return two columns with names id and stringValue, where stringValue
     * is a value based on which localized fallback value will be built
     * @param string $insertQuery query which insert information into relation table, should accept two parameters :id
     * and :valueId as localization value identifier
     */
    public function __construct($selectQuery, $insertQuery)
    {
        $this->selectQuery = $selectQuery;
        $this->insertQuery = $insertQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $this->logQuery($logger, $this->selectQuery);

        $rows  = $this->connection->fetchAll($this->selectQuery);
        foreach ($rows as $row) {
            $this->addLocalizedFallbackValue($logger, $row['id'], $row['value'], $dryRun);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param int $id
     * @param string $value
     * @param bool $dryRun
     */
    protected function addLocalizedFallbackValue(LoggerInterface $logger, $id, $value, $dryRun = false)
    {
        $localizedValueQuery = 'INSERT INTO oro_fallback_localization_val (string) VALUES (:values);';
        $params = ['values' => $value];
        $types = ['values' => 'string'];

        $this->logQuery($logger, $localizedValueQuery, $params, $types);

        if (!$dryRun) {
            $this->connection->executeQuery($localizedValueQuery, $params, $types);
        }

        $params = ['id' => $id, 'valueId' => $this->connection->lastInsertId(
            $this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform
                ? 'oro_fallback_localization_val_id_seq'
                : null
        )];
        $types = ['id' => Type::INTEGER, 'valueId' => Type::INTEGER ];

        $this->logQuery($logger, $this->insertQuery, $params, $types);

        if (!$dryRun) {
            $this->connection->executeQuery($this->insertQuery, $params, $types);
        }
    }
}
