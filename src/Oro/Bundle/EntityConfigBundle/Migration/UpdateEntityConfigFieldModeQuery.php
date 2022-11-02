<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Migration query that hide field from UI.
 */
class UpdateEntityConfigFieldModeQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var string
     */
    protected $mode;

    public function __construct(string $entityName, string $fieldName, string $mode)
    {
        $this->entityName = $entityName;
        $this->fieldName = $fieldName;
        $this->mode = $mode;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->process($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->process($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function process(LoggerInterface $logger, $dryRun = false)
    {
        $selectEntityIdSql = 'SELECT id FROM oro_entity_config WHERE class_name = ? LIMIT 1';
        $parameters = [$this->entityName];
        $row = $this->connection->fetchAssoc($selectEntityIdSql, $parameters);
        if ($row) {
            $updateModeSql = <<<EOF
UPDATE oro_entity_config_field
SET mode = ?
WHERE entity_id = ? AND field_name = ?
EOF;
            $parameters = [$this->mode, $row['id'], $this->fieldName];
            $this->logQuery($logger, $updateModeSql, $parameters);

            if (!$dryRun) {
                $statement = $this->connection->prepare($updateModeSql);
                $statement->execute($parameters);
            }
        }
    }
}
