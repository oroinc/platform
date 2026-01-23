<?php

namespace Oro\Bundle\PlatformBundle\EventListener;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;
use Oro\Bundle\PlatformBundle\Migration\UpdateJsonArrayTypeMigration;

/**
 * Updates JSON array type fields and column comments before migration.
 */
class UpdateJsonArrayTypePreMigrationListener
{
    private DoctrineHelper $doctrineHelper;
    private ConfigManager $configManager;

    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    public function onPreUp(PreMigrationEvent $event): void
    {
        $jsonArrayFields = $this->getJsonArrayFields();

        if ($jsonArrayFields) {
            $this->updateColumnComments();
            $this->updateFieldsType($jsonArrayFields);
            $event->addMigration(new UpdateJsonArrayTypeMigration($jsonArrayFields));
        }
    }

    /**
     * @return FieldConfigModel[]
     */
    private function getJsonArrayFields(): array
    {
        $connection = $this->configManager->getEntityManager()->getConnection();

        if (!$connection->createSchemaManager()->tablesExist(['oro_entity_config_field'])) {
            return [];
        }

        return $this->doctrineHelper
            ->createQueryBuilder(FieldConfigModel::class, 'cf')
            ->andWhere('cf.type = :fieldType')
            ->setParameter('fieldType', 'json_array')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param FieldConfigModel[] $jsonArrayFields
     */
    private function updateFieldsType(array $jsonArrayFields): void
    {
        $em = $this->configManager->getEntityManager();

        foreach ($jsonArrayFields as $field) {
            $field->setType(Types::JSON);
            $em->persist($field);
        }

        $em->flush();
    }

    private function updateColumnComments(): void
    {
        $connection = $this->configManager->getEntityManager()->getConnection();

        $sql = "
            SELECT
                c.table_name,
                c.column_name
            FROM information_schema.columns c
            WHERE c.table_schema = 'public'
              AND pg_catalog.col_description(
                    (c.table_schema||'.'||c.table_name)::regclass::oid,
                    c.ordinal_position
                  ) LIKE '%(DC2Type:json_array)%'
        ";

        $columns = $connection->fetchAllAssociative($sql);

        foreach ($columns as $col) {
            $commentSql = sprintf(
                "COMMENT ON COLUMN %s.%s IS '(DC2Type:json)'",
                $connection->quoteIdentifier($col['table_name']),
                $connection->quoteIdentifier($col['column_name'])
            );

            $connection->executeStatement($commentSql);
        }
    }
}
