<?php

namespace Oro\Bundle\NoteBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Comparator;
use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class UpdateAssociationKindQuery implements MigrationQuery, ConnectionAwareInterface
{
    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @var ActivityExtension
     */
    protected $activityExtension;

    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * UpdateAssociationKindQuery constructor.
     *
     * @param Schema            $schema
     * @param ActivityExtension $activityExtension
     * @param ExtendExtension   $extendExtension
     */
    public function __construct(Schema $schema, ActivityExtension $activityExtension, ExtendExtension $extendExtension)
    {
        $this->schema            = $schema;
        $this->activityExtension = $activityExtension;
        $this->extendExtension   = $extendExtension;
    }


    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Update Note Activity association kind';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $fromSchema = clone $this->schema;

        $sql = 'SELECT id, class_name, data FROM oro_entity_config';
        $entityConfigs = $this->connection->fetchAll($sql);
        $this->logQuery($logger, $sql);

        $noteEntityConfig = null;
        $relatedToNoteEntitiesConfigs = [];
        foreach ($entityConfigs as $entityConfig) {
            $entityConfig['data'] = empty($entityConfig['data'])
                ? []
                : $this->connection->convertToPHPValue($entityConfig['data'], Type::TARRAY);

            if (!empty($entityConfig['data']['note']['enabled'])) {
                $relatedToNoteEntitiesConfigs[] = $entityConfig;
            }

            if ($entityConfig['class_name'] == 'Oro\Bundle\NoteBundle\Entity\Note') {
                $noteEntityConfig = $entityConfig;
            }
        }

        foreach ($relatedToNoteEntitiesConfigs as $entityConfigurationRow) {
            $relatedEntityClassName = $entityConfigurationRow['class_name'];
            $relatedTableName = $this->extendExtension->getTableNameByEntityClass($relatedEntityClassName);
            $this->activityExtension->addActivityAssociation($this->schema, 'oro_note', $relatedTableName);

            $entityConfigurationRow['data']['note']['enabled'] = false;
            $sql = 'UPDATE oro_entity_config SET `data`=? WHERE id=?';
            $parameters = [
                $this->connection->convertToDatabaseValue($entityConfigurationRow['data'], Type::TARRAY),
                $entityConfigurationRow['id']
            ];
            $this->connection->executeUpdate($sql, $parameters);
            $this->logQuery($logger, $sql, $parameters);

            $noteAssociationName = ExtendHelper::buildAssociationName($relatedEntityClassName);
            $sql = 'DELETE FROM oro_entity_config_field WHERE field_name=? AND entity_id=?';
            $parameters = [$noteAssociationName, $noteEntityConfig['id']];
            $this->connection->executeUpdate($sql, $parameters);
            $this->logQuery($logger, $sql, $parameters);
            unset($noteEntityConfig['data']['extend']['schema']['relation'][$noteAssociationName]);
            $relationKeyName = ExtendHelper::buildRelationKey(
                'Oro\Bundle\NoteBundle\Entity\Note',
                $noteAssociationName,
                'manyToOne',
                $relatedEntityClassName
            );
            unset($noteEntityConfig['data']['extend']['relation'][$relationKeyName]);
        }

        $sql = 'UPDATE oro_entity_config SET `data`=? WHERE id=?';
        $parameters = [
            $this->connection->convertToDatabaseValue($noteEntityConfig['data'], Type::TARRAY),
            $noteEntityConfig['id']
        ];
        $this->connection->executeUpdate($sql, $parameters);
        $this->logQuery($logger, $sql, $parameters);

        $comparator = new Comparator();
        $platform   = $this->connection->getDatabasePlatform();
        $schemaDiff = $comparator->compare($fromSchema, $this->schema);
        $queries    = $schemaDiff->toSql($platform);
        foreach ($queries as $query) {
            $this->logQuery($logger, $query);
            $this->connection->executeQuery($query);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $sql
     * @param array           $params
     */
    protected function logQuery(LoggerInterface $logger, $sql, array $params = [])
    {
        $logger->debug(sprintf('Query: %s %s Parameters: %', $sql, PHP_EOL, print_r($params, true)));
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }
}
