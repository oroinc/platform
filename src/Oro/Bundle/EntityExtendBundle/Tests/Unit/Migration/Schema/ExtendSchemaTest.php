<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration\Schema;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;

class ExtendSchemaTest extends \PHPUnit_Framework_TestCase
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
                        ['table1', 'Acme\AcmeBundle\Entity\Entity1']
                    ]
                )
            );
        $this->extendOptionsManager = new ExtendOptionsManager($this->entityClassResolver);
    }

    public function testEmptySchema()
    {
        $schema = new ExtendSchema(
            $this->extendOptionsManager
        );

        $this->assertSchemaTypes($schema);
        $this->assertSchemaSql($schema, []);
        $this->assertExtendOptions($schema, []);
    }

    public function testSchemaConstructor()
    {
        $table1 = new Table(
            'table1',
            [
                new Column('column1', Type::getType('string'), ['comment' => 'test'])
            ]
        );

        $schema = new ExtendSchema(
            $this->extendOptionsManager,
            [$table1]
        );

        $this->assertSchemaTypes($schema);
        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 (column1 VARCHAR(255) NOT NULL COMMENT \'test\')'
            ]
        );
        $this->assertExtendOptions($schema, []);
    }

    public function testSchema()
    {
        $schema = new ExtendSchema($this->extendOptionsManager);

        $table1 = $schema->createTable('table1');
        $table1->addColumn('column1', 'string', ['length' => 100]);
        $configurableColumn1 = $table1->addColumn(
            'configurable_column1',
            'string',
            [
                'length'      => 100,
                'oro_options' => [
                    'datagrid' => ['is_visible' => false, 'other' => 'val'],
                ]
            ]
        );
        $table1->addColumn(
            'extend_column1',
            'string',
            [
                'length'      => 100,
                'oro_options' => [
                    'extend'   => ['is_extend' => true, 'owner' => 'Custom'],
                    'datagrid' => ['is_visible' => false],
                ]
            ]
        );

        $table1->addOption(
            'oro_options',
            [
                'entity' => ['icon' => 'icon1'],
            ]
        );
        $configurableColumn1->setOptions(
            [
                'oro_options' => [
                    'datagrid' => ['is_visible' => true],
                    'form'     => ['is_enabled' => false],
                ]
            ]
        );

        $this->assertSchemaTypes($schema);
        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'column1 VARCHAR(100) NOT NULL, '
                . 'configurable_column1 VARCHAR(100) NOT NULL, '
                . 'field_extend_column1 VARCHAR(100) DEFAULT NULL)'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'configs' => [
                        'entity' => ['icon' => 'icon1']
                    ],
                    'fields' => [
                        'configurable_column1' => [
                            'type'    => 'string',
                            'configs' => [
                                'datagrid' => ['is_visible' => true, 'other' => 'val'],
                                'form' => ['is_enabled' => false],
                            ]
                        ],
                        'extend_column1'       => [
                            'type'    => 'string',
                            'configs' => [
                                'extend'   => ['extend' => true, 'is_extend' => true, 'owner' => 'Custom'],
                                'datagrid' => ['is_visible' => false]
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    protected function assertSchemaTypes(Schema $schema)
    {
        foreach ($schema->getTables() as $table) {
            $this->assertInstanceOf(
                'Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendTable',
                $table
            );
            foreach ($table->getColumns() as $column) {
                $this->assertInstanceOf(
                    'Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendColumn',
                    $column
                );
            }
        }
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
