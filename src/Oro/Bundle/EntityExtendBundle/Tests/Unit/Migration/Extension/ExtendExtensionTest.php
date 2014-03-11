<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration\Extension;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ExtendExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityMetadataHelper;

    /** @var ExtendOptionsManager */
    protected $extendOptionsManager;

    protected function setUp()
    {
        $this->entityMetadataHelper =
            $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper')
                ->disableOriginalConstructor()
                ->getMock();
        $this->entityMetadataHelper->expects($this->any())
            ->method('getEntityClassByTableName')
            ->will(
                $this->returnValueMap(
                    [
                        ['table1', 'Acme\AcmeBundle\Entity\Entity1'],
                        ['table2', 'Acme\AcmeBundle\Entity\Entity2'],
                    ]
                )
            );
        $this->entityMetadataHelper->expects($this->any())
            ->method('getFieldNameByColumnName')
            ->will(
                $this->returnValueMap(
                    [
                        ['table1', 'Acme\AcmeBundle\Entity\Entity1'],
                        ['table2', 'Acme\AcmeBundle\Entity\Entity2'],
                    ]
                )
            );
        $this->extendOptionsManager = new ExtendOptionsManager($this->entityMetadataHelper);
    }

    /**
     * @return ExtendSchema
     */
    protected function getExtendSchema()
    {
        return new ExtendSchema($this->extendOptionsManager, new ExtendDbIdentifierNameGenerator());
    }

    /**
     * @return ExtendExtension
     */
    protected function getExtendExtension()
    {
        $result = new ExtendExtension(
            $this->extendOptionsManager,
            $this->entityMetadataHelper
        );
        $result->setNameGenerator(new ExtendDbIdentifierNameGenerator());

        return $result;
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid entity name. Class: Extend\Entity\Acme\AcmeBundle\Entity\Entity1.
     */
    public function testCreateCustomEntityTableWithInvalidEntityName()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            'Acme\AcmeBundle\Entity\Entity1'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid entity name. Class: Extend\Entity\Extend\Entity\Entity1.
     */
    public function testCreateCustomEntityTableWithFullClassName()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            ExtendConfigDumper::ENTITY . 'Entity1'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid entity name. Class: Extend\Entity\1Entity.
     */
    public function testCreateCustomEntityTableWithNameStartsWithDigit()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            '1Entity'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid entity name. Class: Extend\Entity\_Entity.
     */
    public function testCreateCustomEntityTableWithNameStartsWithUnderscore()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            '_Entity'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid entity name. Class: Extend\Entity\Entity#1.
     */
    public function testCreateCustomEntityTableWithInvalidChars()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            'Entity#1'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Entity name length must be less or equal 22 characters.
     */
    public function testCreateCustomEntityTableWithTooLongName()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            'E1234567891234567890123'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "extend.owner" option for a custom entity must be "Custom".
     */
    public function testCreateCustomEntityTableWithInvalidOwner()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            'Entity1',
            [
                'extend' => ['owner' => ExtendScope::OWNER_SYSTEM],
            ]
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "extend.is_extend" option for a custom entity must be TRUE.
     */
    public function testCreateCustomEntityTableWithInvalidIsExtend()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            'Entity1',
            [
                'extend' => ['is_extend' => false],
            ]
        );
    }

    public function testCreateCustomEntityTable()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            'Entity_1'
        );
        $extension->createCustomEntityTable(
            $schema,
            'Entity2',
            [
                'entity' => ['icon' => 'icon2'],
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
            ]
        );
        $extension->createCustomEntityTable(
            $schema,
            'Entity3',
            [
                'extend' => ['is_extend' => true],
            ]
        );

        $this->assertSchemaSql(
            $schema,
            [
                sprintf(
                    'CREATE TABLE %sentity_1 (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id))',
                    ExtendDbIdentifierNameGenerator::CUSTOM_TABLE_PREFIX
                ),
                sprintf(
                    'CREATE TABLE %sentity2 (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id))',
                    ExtendDbIdentifierNameGenerator::CUSTOM_TABLE_PREFIX
                ),
                sprintf(
                    'CREATE TABLE %sentity3 (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id))',
                    ExtendDbIdentifierNameGenerator::CUSTOM_TABLE_PREFIX
                ),
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                ExtendConfigDumper::ENTITY . 'Entity_1' => [
                    'configs' => [
                        'extend' => [
                            'owner'     => ExtendScope::OWNER_CUSTOM,
                            'is_extend' => true
                        ]
                    ],
                ],
                ExtendConfigDumper::ENTITY . 'Entity2'  => [
                    'configs' => [
                        'extend' => [
                            'owner'     => ExtendScope::OWNER_CUSTOM,
                            'is_extend' => true
                        ],
                        'entity' => ['icon' => 'icon2'],
                    ],
                ],
                ExtendConfigDumper::ENTITY . 'Entity3'  => [
                    'configs' => [
                        'extend' => [
                            'owner'     => ExtendScope::OWNER_CUSTOM,
                            'is_extend' => true
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddOptionSetWithNoOptions()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $extension->addOptionSet(
            $schema,
            $table1,
            'option_set1'
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 (id INT NOT NULL)'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'option_set1' => [
                            'type'    => 'optionSet',
                            'configs' => [
                                'extend' => ['extend' => true]
                            ]
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddOptionSet()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $extension->addOptionSet(
            $schema,
            $table1,
            'option_set1',
            [
                'extend' => ['is_extend' => true]
            ]
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 (id INT NOT NULL)'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'option_set1' => [
                            'type'    => 'optionSet',
                            'configs' => [
                                'extend' => ['extend' => true, 'is_extend' => true]
                            ]
                        ]
                    ],
                ],
            ]
        );
    }

    /**
     * @expectedException \Doctrine\DBAL\Schema\SchemaException
     * @expectedExceptionMessage The table "table1" must have a primary key.
     */
    public function testAddOneToManyRelationWithNoPrimaryKey()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addOneToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );
    }

    /**
     * @expectedException \Doctrine\DBAL\Schema\SchemaException
     * @expectedExceptionMessage A primary key of "table1" table must include only one column.
     */
    public function testAddOneToManyRelationWithCombinedPrimaryKey()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->addColumn('id1', 'integer');
        $table1->setPrimaryKey(['id', 'id1']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addOneToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );
    }

    /**
     * @expectedException \Doctrine\DBAL\Schema\SchemaException
     * @expectedExceptionMessage The table "table2" must have a primary key.
     */
    public function testAddOneToManyRelationWithNoTargetPrimaryKey()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');

        $extension->addOneToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );
    }

    /**
     * @expectedException \Doctrine\DBAL\Schema\SchemaException
     * @expectedExceptionMessage A primary key of "table2" table must include only one column.
     */
    public function testAddOneToManyRelationWithCombinedTargetPrimaryKey()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('id1', 'integer');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id', 'id1']);

        $extension->addOneToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );
    }

    public function testAddOneToManyRelationWithNoOptions()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addOneToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'id INT NOT NULL, '
                . 'default_relation_column1_id SMALLINT DEFAULT NULL, '
                . 'UNIQUE INDEX UNIQ_1C95229D63A7B402 (default_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table2 ('
                . 'id SMALLINT NOT NULL, '
                . 'entity1_relation_column1_id INT DEFAULT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'INDEX IDX_859C7327B0E6CF0B (entity1_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'ALTER TABLE table1 ADD CONSTRAINT FK_1C95229D63A7B402 '
                . 'FOREIGN KEY (default_relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL',
                'ALTER TABLE table2 ADD CONSTRAINT FK_859C7327B0E6CF0B '
                . 'FOREIGN KEY (entity1_relation_column1_id) REFERENCES table1 (id) ON DELETE SET NULL'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'relation_column1' => [
                            'type'    => 'oneToMany',
                            'configs' => [
                                'extend' => [
                                    'extend'          => true,
                                    'target_entity'   => 'Acme\AcmeBundle\Entity\Entity2',
                                    'target_title'    => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid'     => ['name'],
                                    'relation_key'    =>
                                        'oneToMany|Acme\AcmeBundle\Entity\Entity1|'
                                        . 'Acme\AcmeBundle\Entity\Entity2|relation_column1',
                                ]
                            ]
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddOneToManyRelation()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addOneToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name'],
            [
                'extend' => ['is_extend' => true]
            ]
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'id INT NOT NULL, '
                . 'default_relation_column1_id SMALLINT DEFAULT NULL, '
                . 'UNIQUE INDEX UNIQ_1C95229D63A7B402 (default_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table2 ('
                . 'id SMALLINT NOT NULL, '
                . 'entity1_relation_column1_id INT DEFAULT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'INDEX IDX_859C7327B0E6CF0B (entity1_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'ALTER TABLE table1 ADD CONSTRAINT FK_1C95229D63A7B402 '
                . 'FOREIGN KEY (default_relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL',
                'ALTER TABLE table2 ADD CONSTRAINT FK_859C7327B0E6CF0B '
                . 'FOREIGN KEY (entity1_relation_column1_id) REFERENCES table1 (id) ON DELETE SET NULL'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'relation_column1' => [
                            'type'    => 'oneToMany',
                            'configs' => [
                                'extend' => [
                                    'extend'          => true,
                                    'is_extend'       => true,
                                    'target_entity'   => 'Acme\AcmeBundle\Entity\Entity2',
                                    'target_title'    => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid'     => ['name'],
                                    'relation_key'    =>
                                        'oneToMany|Acme\AcmeBundle\Entity\Entity1|'
                                        . 'Acme\AcmeBundle\Entity\Entity2|relation_column1',
                                ]
                            ]
                        ]
                    ],
                ],
            ]
        );
    }

    /**
     * @expectedException \Doctrine\DBAL\Schema\SchemaException
     * @expectedExceptionMessage The table "table1" must have a primary key.
     */
    public function testAddManyToManyRelationWithNoPrimaryKey()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addManyToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );
    }

    /**
     * @expectedException \Doctrine\DBAL\Schema\SchemaException
     * @expectedExceptionMessage A primary key of "table1" table must include only one column.
     */
    public function testAddManyToManyRelationWithCombinedPrimaryKey()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->addColumn('id1', 'integer');
        $table1->setPrimaryKey(['id', 'id1']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addManyToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );
    }

    /**
     * @expectedException \Doctrine\DBAL\Schema\SchemaException
     * @expectedExceptionMessage The table "table2" must have a primary key.
     */
    public function testAddManyToManyRelationWithNoTargetPrimaryKey()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');

        $extension->addManyToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );
    }

    /**
     * @expectedException \Doctrine\DBAL\Schema\SchemaException
     * @expectedExceptionMessage A primary key of "table2" table must include only one column.
     */
    public function testAddManyToManyRelationWithCombinedTargetPrimaryKey()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('id1', 'integer');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id', 'id1']);

        $extension->addManyToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );
    }

    public function testAddManyToManyRelationWithNoOptions()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addManyToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'id INT NOT NULL, '
                . 'default_relation_column1_id SMALLINT DEFAULT NULL, '
                . 'UNIQUE INDEX UNIQ_1C95229D63A7B402 (default_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table2 ('
                . 'id SMALLINT NOT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE oro_rel_f061705960f46bf2d67f27 ('
                . 'entity1_id INT NOT NULL, '
                . 'entity2_id SMALLINT NOT NULL, '
                . 'INDEX IDX_8CE090DAC33725A7 (entity1_id), '
                . 'INDEX IDX_8CE090DAD1828A49 (entity2_id), '
                . 'PRIMARY KEY(entity1_id, entity2_id))',
                'ALTER TABLE table1 ADD CONSTRAINT FK_1C95229D63A7B402 '
                . 'FOREIGN KEY (default_relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL',
                'ALTER TABLE oro_rel_f061705960f46bf2d67f27 ADD CONSTRAINT FK_8CE090DAC33725A7 '
                . 'FOREIGN KEY (entity1_id) REFERENCES table1 (id) ON DELETE CASCADE',
                'ALTER TABLE oro_rel_f061705960f46bf2d67f27 ADD CONSTRAINT FK_8CE090DAD1828A49 '
                . 'FOREIGN KEY (entity2_id) REFERENCES table2 (id) ON DELETE CASCADE'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'relation_column1' => [
                            'type'    => 'manyToMany',
                            'configs' => [
                                'extend' => [
                                    'extend'          => true,
                                    'target_entity'   => 'Acme\AcmeBundle\Entity\Entity2',
                                    'target_title'    => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid'     => ['name'],
                                    'relation_key'    =>
                                        'manyToMany|Acme\AcmeBundle\Entity\Entity1|'
                                        . 'Acme\AcmeBundle\Entity\Entity2|relation_column1',
                                ]
                            ]
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddManyToManyRelation()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addManyToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name'],
            [
                'extend' => ['is_extend' => true]
            ]
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'id INT NOT NULL, '
                . 'default_relation_column1_id SMALLINT DEFAULT NULL, '
                . 'UNIQUE INDEX UNIQ_1C95229D63A7B402 (default_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table2 ('
                . 'id SMALLINT NOT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE oro_rel_f061705960f46bf2d67f27 ('
                . 'entity1_id INT NOT NULL, '
                . 'entity2_id SMALLINT NOT NULL, '
                . 'INDEX IDX_8CE090DAC33725A7 (entity1_id), '
                . 'INDEX IDX_8CE090DAD1828A49 (entity2_id), '
                . 'PRIMARY KEY(entity1_id, entity2_id))',
                'ALTER TABLE table1 ADD CONSTRAINT FK_1C95229D63A7B402 '
                . 'FOREIGN KEY (default_relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL',
                'ALTER TABLE oro_rel_f061705960f46bf2d67f27 ADD CONSTRAINT FK_8CE090DAC33725A7 '
                . 'FOREIGN KEY (entity1_id) REFERENCES table1 (id) ON DELETE CASCADE',
                'ALTER TABLE oro_rel_f061705960f46bf2d67f27 ADD CONSTRAINT FK_8CE090DAD1828A49 '
                . 'FOREIGN KEY (entity2_id) REFERENCES table2 (id) ON DELETE CASCADE'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'relation_column1' => [
                            'type'    => 'manyToMany',
                            'configs' => [
                                'extend' => [
                                    'extend'          => true,
                                    'is_extend'       => true,
                                    'target_entity'   => 'Acme\AcmeBundle\Entity\Entity2',
                                    'target_title'    => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid'     => ['name'],
                                    'relation_key'    =>
                                        'manyToMany|Acme\AcmeBundle\Entity\Entity1|'
                                        . 'Acme\AcmeBundle\Entity\Entity2|relation_column1',
                                ]
                            ]
                        ]
                    ],
                ],
            ]
        );
    }

    /**
     * @expectedException \Doctrine\DBAL\Schema\SchemaException
     * @expectedExceptionMessage The table "table2" must have a primary key.
     */
    public function testAddManyToOneRelationWithNoTargetPrimaryKey()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');

        $extension->addManyToOneRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            'name'
        );
    }

    /**
     * @expectedException \Doctrine\DBAL\Schema\SchemaException
     * @expectedExceptionMessage A primary key of "table2" table must include only one column.
     */
    public function testAddManyToOneRelationWithCombinedTargetPrimaryKey()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('id1', 'integer');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id', 'id1']);

        $extension->addManyToOneRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            'name'
        );
    }

    public function testAddManyToOneRelationWithNoOptions()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'integer');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addManyToOneRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            'name'
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'id INT NOT NULL, '
                . 'relation_column1_id INT DEFAULT NULL, '
                . 'INDEX idx_table1_relation_column1_id (relation_column1_id), PRIMARY KEY(id))',
                'CREATE TABLE table2 (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))',
                'ALTER TABLE table1 ADD CONSTRAINT fk_table1_relation_column1_id '
                . 'FOREIGN KEY (relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'relation_column1' => [
                            'type'    => 'manyToOne',
                            'configs' => [
                                'extend' => [
                                    'extend'        => true,
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity2',
                                    'target_field'  => 'name',
                                    'relation_key'  =>
                                        'manyToOne|Acme\AcmeBundle\Entity\Entity1|'
                                        . 'Acme\AcmeBundle\Entity\Entity2|relation_column1',
                                ]
                            ]
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddManyToOneRelation()
    {
        $schema    = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'integer');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addManyToOneRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            'name',
            [
                'extend' => ['is_extend' => true]
            ]
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'id INT NOT NULL, '
                . 'relation_column1_id INT DEFAULT NULL, '
                . 'INDEX idx_table1_relation_column1_id (relation_column1_id), PRIMARY KEY(id))',
                'CREATE TABLE table2 (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))',
                'ALTER TABLE table1 ADD CONSTRAINT fk_table1_relation_column1_id '
                . 'FOREIGN KEY (relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'relation_column1' => [
                            'type'    => 'manyToOne',
                            'configs' => [
                                'extend' => [
                                    'extend'        => true,
                                    'is_extend'     => true,
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity2',
                                    'target_field'  => 'name',
                                    'relation_key'  =>
                                        'manyToOne|Acme\AcmeBundle\Entity\Entity1|'
                                        . 'Acme\AcmeBundle\Entity\Entity2|relation_column1',
                                ]
                            ]
                        ]
                    ],
                ],
            ]
        );
    }

    protected function assertSchemaSql(Schema $schema, array $expectedSql)
    {
        $sql = $schema->toSql(new MySqlPlatform());
        foreach ($sql as &$el) {
            $el = str_replace(
                ' DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
                '',
                $el
            );
        }
        $this->assertEquals($expectedSql, $sql);
    }

    protected function assertExtendOptions(ExtendSchema $schema, array $expectedOptions)
    {
        $extendOptions = $schema->getExtendOptions();
        $this->assertEquals($expectedOptions, $extendOptions);
    }
}
