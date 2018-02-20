<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveManyToManyRelationQuery;
use Oro\Bundle\EntityExtendBundle\Tests\Functional\Fixture\LoadExtendedRelationsData;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RemoveManyToManyRelationQueryTest extends WebTestCase
{
    /** @var Connection */
    protected $connection;

    /** @var ArrayLogger */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadExtendedRelationsData::class
        ]);

        $this->logger = new ArrayLogger();
        $this->connection = $this->getContainer()->get('doctrine')->getConnection();
    }

    public function testGetDescription()
    {
        $entityClass = \Extend\Entity\TestEntity1::class;
        $entityField = 'uniM2MTargets';

        $migrationQuery = new RemoveManyToManyRelationQuery(
            $entityClass,
            $entityField
        );

        $this->assertEquals(
            'Remove config for relation uniM2MTargets of entity Extend\Entity\TestEntity1',
            $migrationQuery->getDescription()
        );
    }

    public function testExecuteEntityNotFound()
    {
        $entityClass = \Extend\Entity\UnknownEntity::class;
        $entityField = 'uniM2MTargets';

        $migrationQuery = new RemoveManyToManyRelationQuery(
            $entityClass,
            $entityField
        );
        $migrationQuery->setConnection($this->connection);
        $migrationQuery->execute($this->logger);

        $this->assertSame(
            [
                "Entity 'Extend\Entity\UnknownEntity' not found"
            ],
            $this->logger->getMessages()
        );
    }

    public function testExecuteRelationNotFound()
    {
        $entityClass = \Extend\Entity\TestEntity1::class;
        $entityField = 'unknownRelation';

        $migrationQuery = new RemoveManyToManyRelationQuery(
            $entityClass,
            $entityField
        );
        $migrationQuery->setConnection($this->connection);
        $migrationQuery->execute($this->logger);

        $this->assertSame(
            [
                "Relation 'unknownRelation' not found in 'Extend\Entity\TestEntity1'"
            ],
            $this->logger->getMessages()
        );
    }

    public function testExecuteRelationWithoutTarget()
    {
        $entityClass = \Extend\Entity\TestEntity1::class;
        $entityField = 'uniM2MTargets';

        $relationKey = 'manyToMany|Extend\Entity\TestEntity1|Extend\Entity\TestEntity2|uniM2MTargets';

        $entityRow = $this->getEntityRow($entityClass);
        $entityData = $this->connection->convertToPHPValue($entityRow['data'], Type::TARRAY);
        unset(
            $entityData['extend']['relation'][$relationKey],
            $entityData['extend']['schema']['relation'][$entityField],
            $entityData['extend']['schema']['addremove'][$entityField],
            $entityData['extend']['schema']['default'][ExtendConfigDumper::DEFAULT_PREFIX . $entityField]
        );

        $fieldRow = $this->getFieldRow($entityClass, $entityField);

        $migrationQuery = new RemoveManyToManyRelationQuery(
            $entityClass,
            $entityField
        );

        $migrationQuery->setConnection($this->connection);
        $migrationQuery->execute($this->logger);

        $this->assertArrayIntersectEquals(
            [
                'DELETE FROM oro_entity_config_field WHERE id = ?',
                'Parameters:',
                '[1] = ' . $fieldRow['id'],
                'UPDATE oro_entity_config SET data = ? WHERE class_name = ?',
                'Parameters:',
                '[1] = ' . $this->connection->convertToDatabaseValue($entityData, Type::TARRAY),
                '[2] = ' . $entityClass,
            ],
            $this->logger->getMessages()
        );
    }

    public function testExecuteRelationWithTarget()
    {
        $entityClass = \Extend\Entity\TestEntity1::class;
        $entityField = 'biM2MTargets';
        $targetEntityField = 'biM2MOwners';
        $relationKey = 'manyToMany|Extend\Entity\TestEntity1|Extend\Entity\TestEntity2|biM2MTargets';

        $entityRow = $this->getEntityRow($entityClass);
        $entityData = $this->connection->convertToPHPValue($entityRow['data'], Type::TARRAY);
        unset(
            $entityData['extend']['relation'][$relationKey],
            $entityData['extend']['schema']['relation'][$entityField],
            $entityData['extend']['schema']['addremove'][$entityField],
            $entityData['extend']['schema']['default'][ExtendConfigDumper::DEFAULT_PREFIX . $entityField]
        );

        $targetEntityClass = \Extend\Entity\TestEntity2::class;
        $targetEntityRow = $this->getEntityRow($targetEntityClass);
        $targetEntityData = $this->connection->convertToPHPValue($targetEntityRow['data'], Type::TARRAY);
        unset(
            $targetEntityData['extend']['relation'][$relationKey],
            $targetEntityData['extend']['schema']['relation'][$targetEntityField],
            $targetEntityData['extend']['schema']['addremove'][$targetEntityField],
            $targetEntityData['extend']['schema']['default'][ExtendConfigDumper::DEFAULT_PREFIX . $targetEntityField]
        );

        $fieldRow = $this->getFieldRow($entityClass, $entityField);
        $targetFieldRow = $this->getFieldRow($targetEntityClass, $targetEntityField);

        $migrationQuery = new RemoveManyToManyRelationQuery(
            $entityClass,
            $entityField
        );

        $migrationQuery->setConnection($this->connection);
        $migrationQuery->execute($this->logger);

        $this->assertArrayIntersectEquals(
            [
                'DELETE FROM oro_entity_config_field WHERE id = ?',
                'Parameters:',
                '[1] = ' . $fieldRow['id'],
                'UPDATE oro_entity_config SET data = ? WHERE class_name = ?',
                'Parameters:',
                '[1] = ' . $this->connection->convertToDatabaseValue($entityData, Type::TARRAY),
                '[2] = ' . $entityClass,
                'DELETE FROM oro_entity_config_field WHERE id = ?',
                'Parameters:',
                '[1] = ' . $targetFieldRow['id'],
                'UPDATE oro_entity_config SET data = ? WHERE class_name = ?',
                'Parameters:',
                '[1] = ' . $this->connection->convertToDatabaseValue($targetEntityData, Type::TARRAY),
                '[2] = ' . $targetEntityClass
            ],
            $this->logger->getMessages()
        );
    }

    /**
     * @param string $entityClass
     *
     * @return array
     */
    protected function getEntityRow($entityClass)
    {
        $getEntitySql = 'SELECT e.data 
                FROM oro_entity_config as e 
                WHERE e.class_name = ? 
                LIMIT 1';

        return $this->connection->fetchAssoc(
            $getEntitySql,
            [$entityClass]
        );
    }

    /**
     * @param string $entityClass
     * @param string $entityField
     *
     * @return array
     */
    protected function getFieldRow($entityClass, $entityField)
    {
        $getFieldSql = 'SELECT f.id, f.data
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND field_name = ?
            LIMIT 1';

        return $this->connection->fetchAssoc(
            $getFieldSql,
            [
                $entityClass,
                $entityField
            ]
        );
    }
}
