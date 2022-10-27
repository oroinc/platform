<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Helps to entirely move field config from scope to scope.
 */
class MoveEntityConfigFieldValueQuery extends ParametrizedMigrationQuery
{
    /** @var string */
    protected $entityName;

    /** @var string|null */
    protected $fieldName;

    /** @var string */
    protected $fromScope;

    /** @var string */
    protected $fromCode;

    /** @var string */
    protected $toScope;

    /** @var string */
    protected $toCode;

    /**
     * @param string $entityName
     * @param string|null $fieldName If null then will be processed all fields
     * @param string $fromScope
     * @param string $fromCode
     * @param string $toScope
     * @param string $toCode
     */
    public function __construct(
        string $entityName,
        ?string $fieldName,
        string $fromScope,
        string $fromCode,
        string $toScope,
        string $toCode
    ) {
        $this->entityName = $entityName;
        $this->fieldName = $fieldName;
        $this->fromScope = $fromScope;
        $this->fromCode = $fromCode;
        $this->toScope = $toScope;
        $this->toCode = $toCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): array
    {
        $logger = new ArrayLogger();
        $logger->info(
            sprintf(
                'Move entity "%s" config value for field "%s" from "%s" in scope "%s" to "%s" in scope "%s"',
                $this->entityName,
                $this->fieldName,
                $this->fromCode,
                $this->fromScope,
                $this->toCode,
                $this->toScope
            )
        );
        $this->updateFieldConfig($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger): void
    {
        $this->updateFieldConfig($logger);
    }

    protected function updateFieldConfig(LoggerInterface $logger, bool $dryRun = false): void
    {
        $sql = 'SELECT f.id, f.data FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id WHERE e.class_name = ?';
        $params = [$this->entityName];

        if ($this->fieldName !== null) {
            $sql .= ' AND field_name = ?';
            $params[] = [$this->fieldName];
        }

        $rows = $this->connection->fetchAll($sql, $params);
        $sql = 'UPDATE oro_entity_config_field SET data = ? WHERE id = ?';

        foreach ($rows as $row) {
            $id = $row['id'];

            $data = (array) $this->connection->convertToPHPValue($row['data'], Types::ARRAY);
            if (!isset($data[$this->fromScope][$this->fromCode])) {
                continue;
            }

            $data[$this->toScope][$this->toCode] = $data[$this->fromScope][$this->fromCode];
            unset($data[$this->fromScope][$this->fromCode]);

            $params = [$this->connection->convertToDatabaseValue($data, Types::ARRAY), $id];

            $this->logQuery($logger, $sql, $params);

            if (!$dryRun) {
                $statement = $this->connection->prepare($sql);
                $statement->execute($params);
            }
        }
    }
}
