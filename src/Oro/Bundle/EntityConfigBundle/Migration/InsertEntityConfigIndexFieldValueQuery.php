<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class InsertEntityConfigIndexFieldValueQuery implements MigrationQuery, ConnectionAwareInterface
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
    protected $scope;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param string $entityName
     * @param string $fieldName
     * @param string $scope
     * @param string $code
     * @param string $value
     */
    public function __construct($entityName, $fieldName, $scope, $code, $value)
    {
        $this->entityName   = $entityName;
        $this->fieldName    = $fieldName;
        $this->scope        = $scope;
        $this->code         = $code;
        $this->value        = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();

        $this->insertEntityConfigIndexValue($logger, true);
        $logger->info(
            sprintf(
                'Insert config index value "%s" for field "%s" and entity "%s" in scope "%s" to "%s"',
                $this->code,
                $this->fieldName,
                $this->entityName,
                $this->scope,
                $this->value
            )
        );

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->insertEntityConfigIndexValue($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function insertEntityConfigIndexValue(LoggerInterface $logger, $dryRun = false)
    {
        $row = $this->connection->createQueryBuilder()
            ->select('field.id')
            ->from('oro_entity_config_field', 'field')
            ->innerJoin('field', 'oro_entity_config', 'entity', 'entity.id = field.entity_id')
            ->andWhere('field.field_name = :fieldName AND entity.class_name = :entityName')
            ->setParameters([
                'entityName' => $this->entityName,
                'fieldName' => $this->fieldName
            ])
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);

        $sql = "INSERT INTO oro_entity_config_index_value (entity_id, field_id, scope, code, value) 
                VALUES (NULL, ?, ?, ?, ?)";
        $parameters = [$row['id'], $this->scope, $this->code, $this->value];

        $this->logQuery($logger, $sql, $parameters);

        if (!$dryRun) {
            $statement = $this->connection->prepare($sql);
            $statement->execute($parameters);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $sql
     * @param array           $parameters
     */
    protected function logQuery(LoggerInterface $logger, $sql, array $parameters)
    {
        $message = sprintf('%s with parameters [%s]', $sql, implode(', ', $parameters));
        $logger->debug($message);
    }
}
