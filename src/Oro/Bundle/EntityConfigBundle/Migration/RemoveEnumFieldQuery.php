<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Remove enum field data.
 */
class RemoveEnumFieldQuery extends ParametrizedMigrationQuery implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct(protected $entityClass = '', protected $enumField = '', protected $promotion = '')
    {
    }

    #[\Override]
    public function execute(LoggerInterface $logger): void
    {
        $sql = 'SELECT f.id, f.data, f.type
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND f.field_name = ?
            LIMIT 1';

        $fieldRow = $this->connection->fetchAssociative($sql, [$this->entityClass, $this->enumField]);
        if (!$fieldRow) {
            $logger->info("Enum field '{$this->enumField}' from Entity '{$this->entityClass}' is not found");

            return;
        }

        if (!ExtendHelper::isEnumerableType($fieldRow['type'])) {
            $logger->info("Field '{$this->enumField}' from Entity '{$this->entityClass}' is not Enumerable Type");

            return;
        }

        $data = $this->connection->convertToPHPValue($fieldRow['data'], Types::ARRAY);

        $this->deleteEnumData($logger, $fieldRow, $data);
    }

    protected function deleteEnumData(LoggerInterface $logger, array $fieldRow, array $data): void
    {
        $this->executeQuery($logger, 'DELETE FROM oro_entity_config_field WHERE id = ?', [$fieldRow['id']]);

        if (!empty($data['enum']['enum_code'])) {
            $enumCode = $data['enum']['enum_code'];

            $this->removeOptionsTranslate($logger, (string)$enumCode);
            $this->removeEntityFieldValue($logger);

            $this->executeQuery($logger, 'DELETE FROM oro_enum_option WHERE enum_code = ?', [$enumCode]);
        }
    }

    /**
     * @throws DBALException|Exception
     */
    protected function executeQuery(LoggerInterface $logger, $sql, array $parameters = []): void
    {
        $statement = $this->connection->prepare($sql);
        $statement->executeQuery($parameters);
        $this->logQuery($logger, $sql, $parameters);
    }

    /**
     * @throws DBALException
     */
    protected function removeOptionsTranslate(LoggerInterface $logger, string $enumCode): void
    {
        $sql = 'SELECT id, name FROM oro_enum_option WHERE enum_code = ?';
        $enumOptions = $this->connection->fetchAllAssociative($sql, [$enumCode]);
        if (empty($enumOptions)) {
            return;
        }

        if (!array_is_list($enumOptions)) {
            $enumOptions = [$enumOptions];
        }

        $enumOptionIds = array_column($enumOptions, 'id');
        $translationKeys = array_map(
            fn ($id) => ExtendHelper::buildEnumOptionTranslationKey($id),
            $enumOptionIds
        );

        $this->executeQueryWithMultipleValues(
            $logger,
            'DELETE FROM oro_enum_option_trans WHERE foreign_key IN (?)',
            $enumOptionIds
        );

        $this->executeQueryWithMultipleValues(
            $logger,
            'DELETE FROM oro_translation_key WHERE key IN (?)',
            $translationKeys
        );
    }

    /**
     * @throws Exception|DBALException
     */
    protected function removeEntityFieldValue(LoggerInterface $logger): void
    {
        $classTableName = $this->getEntityClassTableName();
        if (!$classTableName) {
            return;
        }

        $sql = 'UPDATE ' . $classTableName
            . ' SET serialized_data = serialized_data - ?'
            . ' WHERE serialized_data->>? IS NOT NULL';
        $this->executeQuery($logger, $sql, [$this->enumField, $this->enumField]);
    }

    /**
     * @throws DBALException
     */
    protected function executeQueryWithMultipleValues(
        LoggerInterface $logger,
        string $sql,
        array $values,
        int $paramType = Connection::PARAM_STR_ARRAY
    ): void {
        if (empty($values)) {
            return;
        }

        $this->connection->executeQuery($sql, [$values], [$paramType]);
        $this->logQuery($logger, $sql, $values);
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Remove '. $this->enumField .' enum field data';
    }

    protected function getEntityClassTableName(): ?string
    {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManagerForClass($this->entityClass);
        $tableName = $em->getClassMetadata($this->entityClass)->getTableName();

        if ($tableName) {
            return $tableName;
        }

        $class = new \ReflectionClass($this->entityClass);
        $attributes = array_filter($class->getAttributes(), function ($attribute) {
            return $attribute->getName() === \Doctrine\ORM\Mapping\Table::class;
        });
        $attribute = reset($attributes);
        if ($attributes) {
            return $attribute->getArguments()['name'] ?? null;
        }

        return null;
    }
}
