<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Migration;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Sets the email.available_in_template (and optionally email.immutable) entity field config option
 * to the specified value for the given entity class and field names, skipping fields whose value
 * was already changed by a user (unless $force is true).
 *
 * When $fieldNames is empty, the update applies to all fields of the entity.
 */
class SetEmailAvailableInTemplateQuery extends ParametrizedMigrationQuery
{
    private array $allowedTypes = [
        'string' => true,
        'text' => true,
        'integer' => true,
        'smallint' => true,
        'bigint' => true,
        'boolean' => true,
        'datetime' => true,
        'date' => true,
        'time' => true,
        'float' => true,
        'decimal' => true,
        'money' => true,
        'percent' => true,
        'enum' => true,
        'multiEnum' => true,
        'file' => true,
        'image' => true,
        'multiFile' => true,
        'multiImage' => true,
        'ref-one' => true,
        'manyToOne' => true,
        'simple_array' => true,
        'guid' => true,
        'ref-many' => true,
        'manyToMany' => true,
        'oneToMany' => true,
    ];

    /**
     * @param string $entityClass The fully-qualified entity class name.
     * @param bool $availableInTemplate The value to set for email.available_in_template.
     * @param array<string> $fieldNames Field names to update; when empty, all fields are processed.
     * @param bool $force When true, the value is set even if already changed by a user.
     * @param bool $immutable When true, also sets email.immutable to true, preventing further changes via the UI.
     */
    public function __construct(
        private readonly string $entityClass,
        private readonly bool $availableInTemplate,
        private readonly array $fieldNames = [],
        private readonly bool $force = false,
        private readonly bool $immutable = false,
    ) {
    }

    public function setAllowedTypes(array $allowedTypes): void
    {
        $this->allowedTypes = $allowedTypes;
    }

    #[\Override]
    public function getDescription(): array
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger): void
    {
        $this->doExecute($logger);
    }

    private function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        $fieldConfigs = $this->loadFieldConfigs($logger);
        if (!$fieldConfigs) {
            return;
        }

        $fieldsChangedByUser = $this->force ? [] : $this->loadFieldsChangedByUser($logger);

        foreach ($fieldConfigs as $fieldName => $fieldConfig) {
            if ($this->shouldSkipField($fieldName, $fieldConfig, $fieldsChangedByUser)) {
                continue;
            }

            $this->updateFieldConfig($logger, $fieldConfig, $dryRun);
        }
    }

    /**
     * @param array<string, string> $fieldsChangedByUser
     */
    private function shouldSkipField(string $fieldName, array $fieldConfig, array $fieldsChangedByUser): bool
    {
        if ($this->fieldNames && !\in_array($fieldName, $this->fieldNames, true)) {
            return true;
        }

        if (!isset($this->allowedTypes[$fieldConfig['type']])) {
            // Skip because this field type does not support email templates.
            return true;
        }

        if (isset($fieldsChangedByUser[$fieldName])) {
            // Skip because the value was already changed by a user.
            return true;
        }

        $data = $fieldConfig['data'];
        if (isset($data['email']['available_in_template'])
            && $data['email']['available_in_template'] === $this->availableInTemplate
            && (!$this->immutable || ($data['email']['immutable'] ?? false) === true)
        ) {
            // Skip because all targeted values are already set to the desired state.
            return true;
        }

        return false;
    }

    private function updateFieldConfig(LoggerInterface $logger, array $fieldConfig, bool $dryRun): void
    {
        $data = $fieldConfig['data'];
        $data['email']['available_in_template'] = $this->availableInTemplate;
        if ($this->immutable) {
            $data['email']['immutable'] = true;
        }

        $sql = 'UPDATE oro_entity_config_field SET data = :data WHERE id = :id';
        $params = ['data' => $data, 'id' => $fieldConfig['id']];
        $types = ['data' => 'array', 'id' => 'integer'];

        $this->logQuery($logger, $sql, $params, $types);

        if (!$dryRun) {
            $this->connection->executeStatement($sql, $params, $types);
        }
    }

    /**
     * Returns a map of field name to field config data for the entity class.
     *
     * @return array<string, array{id: int, type: string, data: array}>
     */
    private function loadFieldConfigs(LoggerInterface $logger): array
    {
        $sql = 'SELECT fc.id, fc.type, fc.field_name, fc.data'
            . ' FROM oro_entity_config ec'
            . ' INNER JOIN oro_entity_config_field fc ON fc.entity_id = ec.id'
            . ' WHERE ec.class_name = :class';
        $params = ['class' => $this->entityClass];
        $types = ['class' => 'string'];

        $this->logQuery($logger, $sql, $params, $types);

        $result = [];
        $rows = $this->connection->fetchAllAssociative($sql, $params, $types);
        foreach ($rows as $row) {
            $result[$row['field_name']] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'data' => $this->connection->convertToPHPValue($row['data'], 'array'),
            ];
        }

        return $result;
    }

    /**
     * Returns the set of field names whose email.available_in_template value was changed by a user.
     *
     * @return array<string, string>
     */
    private function loadFieldsChangedByUser(LoggerInterface $logger): array
    {
        $sql = 'SELECT field_name, diff'
            . ' FROM oro_entity_config_log_diff'
            . ' WHERE class_name = :class AND scope = :scope AND field_name IS NOT NULL';
        $params = ['class' => $this->entityClass, 'scope' => 'email'];
        $types = ['class' => 'string', 'scope' => 'string'];

        $this->logQuery($logger, $sql, $params, $types);

        $result = [];
        $rows = $this->connection->fetchAllAssociative($sql, $params, $types);
        foreach ($rows as $row) {
            $diff = $this->connection->convertToPHPValue($row['diff'], 'array');
            if (isset($diff['available_in_template'])) {
                $fieldName = $row['field_name'];
                $result[$fieldName] = $fieldName;
            }
        }

        return $result;
    }
}
