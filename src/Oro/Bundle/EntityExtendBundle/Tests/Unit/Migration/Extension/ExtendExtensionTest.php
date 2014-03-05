<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration\Extension;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;

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

    public function testCreateExtendTable()
    {
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager);

        $extension->createExtendTable(
            $schema,
            'table1',
            'Acme\AcmeBundle\Entity\Entity1'
        );
        $extension->createExtendTable(
            $schema,
            'table2',
            'Acme\AcmeBundle\Entity\Entity2',
            [
                'entity' => ['icon' => 'icon2'],
                'extend' => ['owner' => 'Custom'],
            ]
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id))',
                'CREATE TABLE table2 (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id))',
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'configs' => [
                        'extend' => ['table' => 'table1']
                    ],
                ],
                'Acme\AcmeBundle\Entity\Entity2' => [
                    'configs' => [
                        'extend' => ['table' => 'table2', 'owner' => 'Custom'],
                        'entity' => ['icon' => 'icon2'],
                    ],
                ],
            ]
        );
    }

    public function testAddOptionSet()
    {
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager);

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
                                'extend' => ['is_extend' => true]
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
        $extension = new ExtendExtension($this->extendOptionsManager);

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
                . 'UNIQUE INDEX UIDX_default_relation_column1_id (default_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table2 ('
                . 'id SMALLINT NOT NULL, '
                . 'field_entity1_relation_column1_id INT DEFAULT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'INDEX IDX_field_entity1_relation_column1_id (field_entity1_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'ALTER TABLE table1 ADD CONSTRAINT FK_1C95229D63A7B402 '
                . 'FOREIGN KEY (default_relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL',
                'ALTER TABLE table2 ADD CONSTRAINT FK_859C73277462FC4 '
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

    public function testAddManyToManyRelation()
    {
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager);

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
                . 'UNIQUE INDEX UIDX_default_relation_column1_id (default_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table2 ('
                . 'id SMALLINT NOT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE oro_entity1_entity2_relation_column1 ('
                . 'entity1_id INT NOT NULL, '
                . 'entity2_id SMALLINT NOT NULL, '
                . 'INDEX IDX_entity1_id (entity1_id), '
                . 'INDEX IDX_entity2_id (entity2_id), '
                . 'PRIMARY KEY(entity1_id, entity2_id))',
                'ALTER TABLE table1 ADD CONSTRAINT FK_1C95229D63A7B402 '
                . 'FOREIGN KEY (default_relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL',
                'ALTER TABLE oro_entity1_entity2_relation_column1 ADD CONSTRAINT FK_672603D1C33725A7 '
                . 'FOREIGN KEY (entity1_id) REFERENCES table1 (id) ON DELETE CASCADE',
                'ALTER TABLE oro_entity1_entity2_relation_column1 ADD CONSTRAINT FK_672603D1D1828A49 '
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

    public function testAddManyToOneRelation()
    {
        $schema    = new ExtendSchema($this->extendOptionsManager);
        $extension = new ExtendExtension($this->extendOptionsManager);

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
                . 'INDEX IDX_field_relation_column1_id (field_relation_column1_id), PRIMARY KEY(id))',
                'CREATE TABLE table2 (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))',
                'ALTER TABLE table1 ADD CONSTRAINT FK_1C95229DF1043101 '
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
