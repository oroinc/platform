<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration\Extension;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityExtendBundle\Tools\DatabaseIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class ExtendExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityClassResolver;

    /** @var ExtendOptionsManager */
    protected $extendOptionsManager;

    protected function setUp()
    {
        $this->entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityClassResolver->expects($this->any())
            ->method('getEntityClassByTableName')
            ->will(
                $this->returnValueMap(
                    [
                        ['table1', 'Acme\AcmeBundle\Entity\Entity1'],
                        ['table2', 'Acme\AcmeBundle\Entity\Entity2'],
                    ]
                )
            );
        $this->entityClassResolver->expects($this->any())
            ->method('getFieldNameByColumnName')
            ->will(
                $this->returnValueMap(
                    [
                        ['table1', 'Acme\AcmeBundle\Entity\Entity1'],
                        ['table2', 'Acme\AcmeBundle\Entity\Entity2'],
                    ]
                )
            );
        $this->extendOptionsManager = new ExtendOptionsManager($this->entityClassResolver);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid entity name: "Acme\AcmeBundle\Entity\Entity1".
     */
    public function testCreateCustomEntityTableWithInvalidEntityName1()
    {
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

        $extension->createCustomEntityTable(
            $schema,
            'table1',
            'Acme\AcmeBundle\Entity\Entity1'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid entity name: "Extend\Entity\Entity1".
     */
    public function testCreateCustomEntityTableWithInvalidEntityName2()
    {
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

        $extension->createCustomEntityTable(
            $schema,
            'table1',
            ExtendConfigDumper::ENTITY . 'Entity1'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "extend.owner" option for a custom entity must be "Custom".
     */
    public function testCreateCustomEntityTableWithInvalidOwner()
    {
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

        $extension->createCustomEntityTable(
            $schema,
            'table1',
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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

        $extension->createCustomEntityTable(
            $schema,
            'table1',
            'Entity1',
            [
                'extend' => ['is_extend' => false],
            ]
        );
    }

    public function testCreateCustomEntityTable()
    {
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

        $extension->createCustomEntityTable(
            $schema,
            'table1',
            'Entity1'
        );
        $extension->createCustomEntityTable(
            $schema,
            'table2',
            'Entity2',
            [
                'entity' => ['icon' => 'icon2'],
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
            ]
        );
        $extension->createCustomEntityTable(
            $schema,
            'table3',
            'Entity3',
            [
                'extend' => ['is_extend' => true],
            ]
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id))',
                'CREATE TABLE table2 (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id))',
                'CREATE TABLE table3 (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id))',
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                ExtendConfigDumper::ENTITY . 'Entity1' => [
                    'configs' => [
                        'extend' => ['table' => 'table1', 'owner' => ExtendScope::OWNER_CUSTOM, 'is_extend' => true]
                    ],
                ],
                ExtendConfigDumper::ENTITY . 'Entity2' => [
                    'configs' => [
                        'extend' => ['table' => 'table2', 'owner' => ExtendScope::OWNER_CUSTOM, 'is_extend' => true],
                        'entity' => ['icon' => 'icon2'],
                    ],
                ],
                ExtendConfigDumper::ENTITY . 'Entity3' => [
                    'configs' => [
                        'extend' => ['table' => 'table3', 'owner' => ExtendScope::OWNER_CUSTOM, 'is_extend' => true]
                    ],
                ],
            ]
        );
    }

    public function testAddOptionSetWithNoOptions()
    {
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
                . 'UNIQUE INDEX uidx_63a7b4021c95229d (default_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table2 ('
                . 'id SMALLINT NOT NULL, '
                . 'field_entity1_relation_column1_id INT DEFAULT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'INDEX idx_7462fc4859c7327 (field_entity1_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'ALTER TABLE table1 ADD CONSTRAINT fk_63a7b402bf3967501c95229d859 '
                . 'FOREIGN KEY (default_relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL',
                'ALTER TABLE table2 ADD CONSTRAINT fk_7462fc4bf396750859c73271c95 '
                . 'FOREIGN KEY (field_entity1_relation_column1_id) REFERENCES table1 (id) ON DELETE SET NULL'
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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
                . 'UNIQUE INDEX uidx_63a7b4021c95229d (default_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table2 ('
                . 'id SMALLINT NOT NULL, '
                . 'field_entity1_relation_column1_id INT DEFAULT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'INDEX idx_7462fc4859c7327 (field_entity1_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'ALTER TABLE table1 ADD CONSTRAINT fk_63a7b402bf3967501c95229d859 '
                . 'FOREIGN KEY (default_relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL',
                'ALTER TABLE table2 ADD CONSTRAINT fk_7462fc4bf396750859c73271c95 '
                . 'FOREIGN KEY (field_entity1_relation_column1_id) REFERENCES table1 (id) ON DELETE SET NULL'
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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
                . 'UNIQUE INDEX uidx_63a7b4021c95229d (default_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table2 ('
                . 'id SMALLINT NOT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE acme_2d67f27af061705960f46bf ('
                . 'entity1_id INT NOT NULL, '
                . 'entity2_id SMALLINT NOT NULL, '
                . 'INDEX idx_c33725a7855f9652 (entity1_id), '
                . 'INDEX idx_d1828a49855f9652 (entity2_id), '
                . 'PRIMARY KEY(entity1_id, entity2_id))',
                'ALTER TABLE table1 ADD CONSTRAINT fk_63a7b402bf3967501c95229d859 '
                . 'FOREIGN KEY (default_relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL',
                'ALTER TABLE acme_2d67f27af061705960f46bf ADD CONSTRAINT fk_c33725a7bf396750855f96521c9 '
                . 'FOREIGN KEY (entity1_id) REFERENCES table1 (id) ON DELETE CASCADE',
                'ALTER TABLE acme_2d67f27af061705960f46bf ADD CONSTRAINT fk_d1828a49bf396750855f9652859 '
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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
                . 'UNIQUE INDEX uidx_63a7b4021c95229d (default_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table2 ('
                . 'id SMALLINT NOT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE acme_2d67f27af061705960f46bf ('
                . 'entity1_id INT NOT NULL, '
                . 'entity2_id SMALLINT NOT NULL, '
                . 'INDEX idx_c33725a7855f9652 (entity1_id), '
                . 'INDEX idx_d1828a49855f9652 (entity2_id), '
                . 'PRIMARY KEY(entity1_id, entity2_id))',
                'ALTER TABLE table1 ADD CONSTRAINT fk_63a7b402bf3967501c95229d859 '
                . 'FOREIGN KEY (default_relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL',
                'ALTER TABLE acme_2d67f27af061705960f46bf ADD CONSTRAINT fk_c33725a7bf396750855f96521c9 '
                . 'FOREIGN KEY (entity1_id) REFERENCES table1 (id) ON DELETE CASCADE',
                'ALTER TABLE acme_2d67f27af061705960f46bf ADD CONSTRAINT fk_d1828a49bf396750855f9652859 '
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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
                . 'field_relation_column1_id INT DEFAULT NULL, '
                . 'INDEX idx_f10431011c95229d (field_relation_column1_id), PRIMARY KEY(id))',
                'CREATE TABLE table2 (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))',
                'ALTER TABLE table1 ADD CONSTRAINT fk_f1043101bf3967501c95229d859 '
                . 'FOREIGN KEY (field_relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL'
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
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager, new DatabaseIdentifierNameGenerator());

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
                . 'field_relation_column1_id INT DEFAULT NULL, '
                . 'INDEX idx_f10431011c95229d (field_relation_column1_id), PRIMARY KEY(id))',
                'CREATE TABLE table2 (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))',
                'ALTER TABLE table1 ADD CONSTRAINT fk_f1043101bf3967501c95229d859 '
                . 'FOREIGN KEY (field_relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL'
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
        $extendOptions = $schema->getExtendOptionsProvider()->getOptions();
        $this->assertEquals($expectedOptions, $extendOptions);
    }
}
