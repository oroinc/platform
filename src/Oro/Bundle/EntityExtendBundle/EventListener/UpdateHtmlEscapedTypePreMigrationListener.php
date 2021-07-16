<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateHtmlEscapedTypeMigration;
use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;

/**
 * Updates html_escaped columns to text type
 */
class UpdateHtmlEscapedTypePreMigrationListener
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ConfigManager */
    private $configManager;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    /**
     * PRE UP event handler
     */
    public function onPreUp(PreMigrationEvent $event): void
    {
        $htmlEscapedFields = $this->getHtmlEscapedFields();
        if ($htmlEscapedFields) {
            $this->updateFieldsType($htmlEscapedFields);
            $event->addMigration(
                new UpdateHtmlEscapedTypeMigration(
                    $this->configManager->getEntityManager(),
                    $htmlEscapedFields
                )
            );
        }
    }

    /**
     * @return FieldConfigModel[]
     */
    private function getHtmlEscapedFields(): array
    {
        $connection = $this->configManager->getEntityManager()->getConnection();
        if (!$connection->getSchemaManager()->tablesExist(['oro_entity_config_field'])) {
            return [];
        }

        return $this->doctrineHelper->createQueryBuilder(FieldConfigModel::class, 'cf')
            ->andWhere('cf.type = :fieldType')
            ->setParameter('fieldType', 'html_escaped')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param FieldConfigModel[] $htmlEscapedFields
     */
    private function updateFieldsType(array $htmlEscapedFields): void
    {
        $connection = $this->configManager->getEntityManager()->getConnection();
        $extendConfigProvider = $this->configManager->getProvider('extend');

        foreach ($htmlEscapedFields as $htmlEscapedField) {
            $entityClassName = $htmlEscapedField->getEntity()->getClassName();
            $fieldConfig = $extendConfigProvider->getConfig($entityClassName, $htmlEscapedField->getFieldName());

            if (!$fieldConfig->is('is_serialized')) {
                $tableName = $this->doctrineHelper
                    ->getEntityMetadataForClass($entityClassName)
                    ->getTableName();

                $this->changeColumn($connection, $tableName, $htmlEscapedField->getFieldName());
            }
        }
    }

    private function changeColumn(Connection $connection, string $tableName, string $fieldName): void
    {
        $isColumnExistsQuery = <<<SQL
            SELECT COLUMN_NAME FROM information_schema.COLUMNS
            WHERE TABLE_NAME = :tableName AND COLUMN_NAME = :fieldName
SQL;
        $isColumnExists = $connection->executeQuery(
            $isColumnExistsQuery,
            ['tableName' => $tableName, 'fieldName' => $fieldName],
            ['tableName' => Types::STRING, 'fieldName' => Types::STRING]
        )
        ->fetchColumn();

        if (!$isColumnExists) {
            return;
        }

        $platform = $connection->getDatabasePlatform();
        if ($platform instanceof PostgreSqlPlatform) {
            $connection->executeStatement(
                \sprintf(
                    'ALTER TABLE %s ALTER COLUMN %s TYPE text',
                    $tableName,
                    $fieldName
                )
            );
            $connection->executeStatement(
                \sprintf(
                    'COMMENT ON COLUMN %s.%s IS NULL',
                    $tableName,
                    $fieldName
                )
            );
        }

        if ($platform instanceof MySqlPlatform) {
            $connection->executeStatement(
                \sprintf(
                    'ALTER TABLE %s CHANGE %s %s text',
                    $tableName,
                    $fieldName,
                    $fieldName
                )
            );
        }
    }
}
