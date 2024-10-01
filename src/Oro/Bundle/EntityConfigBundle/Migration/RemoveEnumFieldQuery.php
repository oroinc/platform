<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

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

            $sql = 'SELECT id, name FROM oro_enum_option WHERE enum_code = ?';
            $enumOptions = $this->connection->fetchAssociative($sql, [$enumCode]);
            $classTableName = $this->getEntityClassTableName();
            if (!empty($enumOptions)) {
                if (!array_is_list($enumOptions)) {
                    $enumOptions = [$enumOptions];
                }
                foreach ($enumOptions as $enumOption) {
                    $enumOptionId = $enumOption['id'];
                    $enumOptionName = $enumOption['name'];
                    $translationKey = ExtendHelper::buildEnumOptionTranslationKey($enumOptionId);

                    $this->executeQuery(
                        $logger,
                        'DELETE FROM oro_enum_option_trans WHERE foreign_key = ?',
                        [$enumOptionId]
                    );
                    $this->executeQuery($logger, 'DELETE FROM oro_translation_key WHERE key = ?', [$translationKey]);

                    if ($classTableName) {
                        if (ExtendHelper::isMultiEnumType($fieldRow['type'])) {
                            $sql = "UPDATE $classTableName " .
                                "SET serialized_data = jsonb_set(serialized_data::jsonb, '{{$enumOptionName}}', " .
                                "(serialized_data->'$enumOptionName')::jsonb - NULL) " .
                                "WHERE serialized_data->>'$enumOptionName' IS NOT NULL " .
                                "AND (serialized_data->'$enumOptionName' @> ('\"$enumOptionId\"')::jsonb) = true";
                            $this->executeQuery($logger, $sql);
                        } else {
                            $sql = "UPDATE $classTableName SET serialized_data = serialized_data - '$enumOptionName'" .
                            " WHERE serialized_data->>'$enumOptionName' = '$enumOptionId'";
                            $this->executeQuery($logger, $sql);
                        }
                    }
                }
            }

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
