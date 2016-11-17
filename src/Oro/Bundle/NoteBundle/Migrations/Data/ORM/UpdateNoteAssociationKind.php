<?php

namespace Oro\Bundle\NoteBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\NoteBundle\Entity\Note;

class UpdateNoteAssociationKind extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param EntityManager $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $connection = $manager->getConnection();

        $sql = 'SELECT id, class_name, data FROM oro_entity_config';
        $entityConfigs = $connection->fetchAll($sql);
        $entityConfigs = array_map(function ($entityConfig) use ($connection) {
            $entityConfig['data'] = empty($entityConfig['data'])
                ? []
                : $connection->convertToPHPValue($entityConfig['data'], Type::TARRAY);

            return $entityConfig;
        }, $entityConfigs);

        foreach ($entityConfigs as $entityConfig) {
            if (!empty($entityConfig['data']['note']['enabled'])) {
                $this->migrateNoteRelationToActivityRelationKind($connection, $entityConfig['class_name']);
            }

            unset($entityConfig['data']['note']);
            $connection->executeUpdate(
                'UPDATE oro_entity_config SET data=? WHERE id=?',
                [
                    $connection->convertToDatabaseValue($entityConfig['data'], Type::TARRAY),
                    $entityConfig['id']
                ]
            );
        }
    }

    /**
     * @param Connection $connection
     * @param string     $className
     */
    protected function migrateNoteRelationToActivityRelationKind(Connection $connection, $className)
    {
        $entityMetadataHelper = $this->container->get('oro_entity_extend.migration.entity_metadata_helper');
        $noteTableName = $entityMetadataHelper->getTableNameByEntityClass(Note::class);

        /** @var ExtendDbIdentifierNameGenerator $nameGenerator */
        $nameGenerator = $this->container->get('oro_entity_extend.db_id_name_generator');

        $associationName = ExtendHelper::buildAssociationName($className, ActivityScope::ASSOCIATION_KIND);
        $associationTableName = $nameGenerator->generateManyToManyJoinTableName(
            Note::class,
            $associationName,
            $className
        );

        $associationColumnName = $nameGenerator->generateManyToManyJoinTableColumnName($className);

        $noteAssociationName = ExtendHelper::buildAssociationName($className);
        $noteAssociationColumnName = $nameGenerator->generateRelationColumnName($noteAssociationName);

        $sql = <<<SQL
          INSERT INTO $associationTableName (note_id, $associationColumnName)
          SELECT id, $noteAssociationColumnName
          FROM $noteTableName WHERE $noteAssociationColumnName IS NOT NULL
SQL;
        $connection->executeUpdate($sql);

        $schemaManager = $connection->getSchemaManager();

        $foreignKeys = array_filter(
            $schemaManager->listTableForeignKeys($noteTableName),
            function (ForeignKeyConstraint $foreignKeyConstraint) use ($noteAssociationColumnName) {
                return in_array($noteAssociationColumnName, $foreignKeyConstraint->getColumns());
            }
        );

        foreach ($foreignKeys as $foreignKey) {
            $schemaManager->dropForeignKey($foreignKey, $noteTableName);
        }

        $sql = "ALTER TABLE {$noteTableName} DROP COLUMN {$noteAssociationColumnName}";
        $connection->executeUpdate($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
