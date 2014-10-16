<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class SerializedDataMigrationQuery extends ParametrizedMigrationQuery
{
    /** @var Schema */
    protected $schema;

    /** @var EntityMetadataHelper */
    protected $metadataHelper;

    /**
     * @param Schema               $schema
     * @param EntityMetadataHelper $metadataHelper
     */
    public function __construct(Schema $schema, EntityMetadataHelper $metadataHelper)
    {
        $this->schema         = $schema;
        $this->metadataHelper = $metadataHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->runSerializedData($logger);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->runSerializedData($logger, true);

        return $logger->getMessages();
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function runSerializedData(LoggerInterface $logger, $dryRun = false)
    {
        $entities         = $this->getConfigurableEntitiesData($logger);
        $hasSchemaChanges = false;
        $toSchema         = clone $this->schema;
        foreach ($entities as $entityClass => $config) {
            if (isset($config['extend']['has_serialized_data']) && $config['extend']['has_serialized_data'] == true) {
                $table = $toSchema->getTable($this->metadataHelper->getTableNameByEntityClass($entityClass));
                if (!$table->hasColumn('serialized_data')) {
                    $hasSchemaChanges = true;
                    $table->addColumn('serialized_data', 'array', ['notnull' => false]);
                }
            }
        }

        if ($hasSchemaChanges) {
            $comparator = new Comparator();
            $platform   = $this->connection->getDatabasePlatform();
            $schemaDiff = $comparator->compare($this->schema, $toSchema);
            $queries    = $schemaDiff->toSql($platform);

            foreach ($queries as $query) {
                $this->logQuery($logger, $query);
                if (!$dryRun) {
                    $this->connection->executeQuery($query);
                }
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     *  key - class name
     *  value - entity config array data
     */
    protected function getConfigurableEntitiesData(LoggerInterface $logger)
    {
        $sql = 'SELECT class_name, data FROM oro_entity_config';
        $this->logQuery($logger, $sql);

        $result = [];
        $rows   = $this->connection->fetchAll($sql);
        foreach ($rows as $row) {
            $result[$row['class_name']] = $this->connection->convertToPHPValue($row['data'], 'array');
        }

        return $result;
    }
}
