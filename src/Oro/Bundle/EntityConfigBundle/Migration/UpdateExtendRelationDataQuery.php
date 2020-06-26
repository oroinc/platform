<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * This query updates extend relation data value for the specific option.
 */
class UpdateExtendRelationDataQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $relationName;

    /**
     * @var string
     */
    protected $option;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @param string $className
     * @param string $relationName
     * @param string $option
     * @param mixed $value
     */
    public function __construct(string $className, string $relationName, string $option, $value)
    {
        $this->className = $className;
        $this->relationName = $relationName;
        $this->option = $option;
        $this->value = $value;
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
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $sql = 'SELECT id, data FROM oro_entity_config WHERE class_name = :className LIMIT 1';
        $parameters = ['className' => $this->className];
        $types = ['className' => Types::STRING];

        $this->logQuery($logger, $sql, $parameters);

        $row = $this->connection->fetchAssoc($sql, $parameters, $types);
        $id = $row['id'];
        $data = isset($row['data']) ? $this->connection->convertToPHPValue($row['data'], Types::ARRAY) : [];

        if (!isset($data['extend']['relation'][$this->relationName])) {
            $logger->error(sprintf(
                'There is no such relation for the class "%s" with the relation name "%s".',
                $this->className,
                $this->relationName
            ));
            return;
        }

        $data['extend']['relation'][$this->relationName][$this->option] = $this->value;
        $data = $this->connection->convertToDatabaseValue($data, Types::ARRAY);

        $sql = 'UPDATE oro_entity_config SET data = ? WHERE id = ?';
        $parameters = [$data, $id];
        $this->logQuery($logger, $sql, $parameters);

        if (!$dryRun) {
            $statement = $this->connection->prepare($sql);
            $statement->execute($parameters);
        }
    }
}
