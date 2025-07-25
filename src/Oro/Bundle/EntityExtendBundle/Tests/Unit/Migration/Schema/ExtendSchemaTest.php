<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration\Schema;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\EntityConfig\Mock\ConfigurationHandlerMock;
use Oro\Bundle\EntityExtendBundle\Configuration\EntityExtendConfigurationProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsParser;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendColumn;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendTable;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExtendSchemaTest extends TestCase
{
    private EntityMetadataHelper&MockObject $entityMetadataHelper;
    private ExtendOptionsManager $extendOptionsManager;
    private ExtendOptionsParser $extendOptionsParser;
    private ExtendDbIdentifierNameGenerator $nameGenerator;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityMetadataHelper = $this->createMock(EntityMetadataHelper::class);
        $this->entityMetadataHelper->expects($this->any())
            ->method('getEntityClassesByTableName')
            ->willReturnMap([
                ['table1', ['Acme\AcmeBundle\Entity\Entity1']],
            ]);
        $this->extendOptionsManager = new ExtendOptionsManager(ConfigurationHandlerMock::getInstance());

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('hasConfig')
            ->willReturn(true);

        $entityExtendConfigurationProvider = $this->createMock(EntityExtendConfigurationProvider::class);
        $entityExtendConfigurationProvider->expects(self::any())
            ->method('getUnderlyingTypes')
            ->willReturn(['enum' => 'manyToOne', 'multiEnum' => 'manyToMany']);

        $this->extendOptionsParser = new ExtendOptionsParser(
            $this->entityMetadataHelper,
            new FieldTypeHelper($entityExtendConfigurationProvider),
            $configManager
        );

        $this->nameGenerator = new ExtendDbIdentifierNameGenerator();
    }

    public function testEmptySchema(): void
    {
        $schema = new ExtendSchema(
            $this->extendOptionsManager,
            $this->nameGenerator
        );

        $this->assertSchemaTypes($schema);
        $this->assertSchemaSql($schema, []);
        $this->assertExtendOptions($schema, []);
    }

    public function testSchemaConstructor(): void
    {
        $table1 = new Table(
            'table1',
            [
                new Column('column1', Type::getType('string'), ['comment' => 'test'])
            ]
        );

        $schema = new ExtendSchema(
            $this->extendOptionsManager,
            $this->nameGenerator,
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSchema(): void
    {
        $this->entityMetadataHelper->expects($this->exactly(3))
            ->method('isEntityClassContainsColumn')
            ->willReturnMap([
                ['Acme\AcmeBundle\Entity\Entity1', 'ref_column1', true],
                ['Acme\AcmeBundle\Entity\Entity1', 'ref_column2', true],
                ['Acme\AcmeBundle\Entity\Entity1', 'configurable_column1', true],
            ]);

        $schema = new ExtendSchema(
            $this->extendOptionsManager,
            $this->nameGenerator
        );

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'integer', ['autoincrement' => true]);

        $table1 = $schema->createTable('table1');
        $table1->addColumn('column1', 'string', ['length' => 100]);
        $configurableColumn1 = $table1->addColumn(
            'configurable_column1',
            'string',
            [
                'length'        => 100,
                OroOptions::KEY => [
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE, 'other' => 'val'],
                ]
            ]
        );
        $table1->addColumn(
            'extend_column1',
            'string',
            [
                'length'        => 100,
                OroOptions::KEY => [
                    'extend'   => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                ]
            ]
        );
        $table1->addColumn(
            'ref_column1',
            'integer',
            [
                OroOptions::KEY => [
                    ExtendOptionsManager::TYPE_OPTION => 'ref-one'
                ]
            ]
        );
        $table1->addForeignKeyConstraint(
            $table2,
            ['ref_column1'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table1->addColumn(
            'ref_column2',
            'integer',
            [
                OroOptions::KEY => [
                    ExtendOptionsManager::TYPE_OPTION => 'ref-one'
                ]
            ]
        );
        $table1->addIndex(['ref_column2'], 'idx_ref_column2');
        $table1->addForeignKeyConstraint(
            $table2,
            ['ref_column2'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );

        $table1->addOption('comment', 'test');

        $table1->addOption(
            OroOptions::KEY,
            [
                'entity' => ['icon' => 'icon1'],
            ]
        );
        $configurableColumn1->setOptions(
            [
                OroOptions::KEY => [
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_TRUE],
                    'form'     => ['is_enabled' => false],
                ]
            ]
        );

        $this->assertSchemaTypes($schema);
        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table2 (id INT AUTO_INCREMENT NOT NULL)',
                'CREATE TABLE table1 ('
                . 'ref_column1 INT NOT NULL, '
                . 'ref_column2 INT NOT NULL, '
                . 'column1 VARCHAR(100) NOT NULL, '
                . 'configurable_column1 VARCHAR(100) NOT NULL, '
                . 'extend_column1 VARCHAR(100) DEFAULT NULL, '
                . 'INDEX IDX_1C95229DF008B3DB (ref_column1), '
                . 'INDEX idx_ref_column2 (ref_column2)) '
                . 'COMMENT = \'test\' ',
                'ALTER TABLE table1 ADD CONSTRAINT fk_table1_ref_column1 '
                . 'FOREIGN KEY (ref_column1) REFERENCES table2 (id) ON DELETE CASCADE',
                'ALTER TABLE table1 ADD CONSTRAINT fk_table1_ref_column2 '
                . 'FOREIGN KEY (ref_column2) REFERENCES table2 (id) ON DELETE CASCADE'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'configs' => [
                        'entity' => ['icon' => 'icon1']
                    ],
                    'fields'  => [
                        'configurable_column1' => [
                            'type'    => 'string',
                            'configs' => [
                                'extend'   => ['length' => 100],
                                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_TRUE, 'other' => 'val'],
                                'form'     => ['is_enabled' => false],
                            ]
                        ],
                        'extend_column1'       => [
                            'type'    => 'string',
                            'configs' => [
                                'extend'   => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_CUSTOM,
                                    'length' => 100,
                                    'nullable' => true
                                ],
                                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE]
                            ]
                        ],
                        'ref_column1'          => [
                            'type' => 'ref-one'
                        ],
                        'ref_column2'          => [
                            'type' => 'ref-one'
                        ]
                    ]
                ]
            ]
        );
    }

    private function assertSchemaTypes(Schema $schema)
    {
        foreach ($schema->getTables() as $table) {
            $this->assertInstanceOf(
                ExtendTable::class,
                $table
            );
            foreach ($table->getColumns() as $column) {
                $this->assertInstanceOf(
                    ExtendColumn::class,
                    $column
                );
            }
        }
    }

    private function assertSchemaSql(Schema $schema, array $expectedSql)
    {
        $sql = $schema->toSql(new MySqlPlatform());
        foreach ($sql as &$el) {
            $el = str_replace(
                ' DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB',
                '',
                $el
            );
        }
        $this->assertEquals($expectedSql, $sql);
    }

    private function assertExtendOptions(ExtendSchema $schema, array $expectedOptions)
    {
        $extendOptions = $schema->getExtendOptions();
        $extendOptions = $this->extendOptionsParser->parseOptions($extendOptions);
        $this->assertEquals($expectedOptions, $extendOptions);
    }
}
