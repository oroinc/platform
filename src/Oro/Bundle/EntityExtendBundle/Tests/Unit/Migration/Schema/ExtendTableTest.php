<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration\Schema;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendColumn;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendTable;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExtendTableTest extends TestCase
{
    const TABLE_NAME = 'test_table';
    const COLUMN_NAME = 'test_column';

    /** @var ExtendOptionsManager|MockObject */
    private $extendOptionsManager;

    /** @var DbIdentifierNameGenerator|MockObject */
    private $nameGenerator;

    /** @var ExtendTable */
    private $table;

    protected function setUp(): void
    {
        $this->extendOptionsManager = $this->createMock(ExtendOptionsManager::class);

        $this->nameGenerator = $this->createMock(DbIdentifierNameGenerator::class);

        $this->table = new ExtendTable([
            'extendOptionsManager' => $this->extendOptionsManager,
            'nameGenerator' => $this->nameGenerator,
            'table' => new Table(self::TABLE_NAME),
        ]);
    }

    public function testAddColumnWithSameExtendLength()
    {
        $this->setExpectations('string', 'length', 100);
        $options = ['length' => 100, OroOptions::KEY => ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]];
        $column = $this->table->addColumn(self::COLUMN_NAME, 'string', $options);
        static::assertInstanceOf(ExtendColumn::class, $column);
        static::assertEquals(100, $column->getLength());
    }

    public function testAddColumnWithSameExtendPrecision()
    {
        $this->setExpectations('float', 'precision', 8);
        $options = ['precision' => 8, OroOptions::KEY => ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]];
        $column = $this->table->addColumn(self::COLUMN_NAME, 'float', $options);
        static::assertInstanceOf(ExtendColumn::class, $column);
        static::assertEquals(8, $column->getPrecision());
    }

    public function testAddColumnWithSameExtendScale()
    {
        $this->setExpectations('float', 'scale', 5);
        $options = ['scale' => 5, OroOptions::KEY => ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]];
        $column = $this->table->addColumn(self::COLUMN_NAME, 'float', $options);
        static::assertInstanceOf(ExtendColumn::class, $column);
        static::assertEquals(5, $column->getScale());
    }

    public function testAddColumnWithSameExtendDefault()
    {
        $this->setExpectations('string', 'default', 'N/A');
        $options = ['default' => 'N/A', OroOptions::KEY => ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]];
        $column = $this->table->addColumn(self::COLUMN_NAME, 'string', $options);
        static::assertInstanceOf(ExtendColumn::class, $column);
        static::assertEquals('N/A', $column->getDefault());
    }

    public function testAddColumnWithSameExtendNullable()
    {
        $type = Type::getType('string');

        $this->extendOptionsManager->expects(static::exactly(2))
            ->method('setColumnOptions')
            ->withConsecutive(
                [
                    self::TABLE_NAME,
                    self::COLUMN_NAME,
                    [
                        'extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'is_extend' => true],
                        '_type' => $type->getName()
                    ]
                ],
                [
                    self::TABLE_NAME,
                    self::COLUMN_NAME,
                    [
                        'extend' => ['nullable' => false],
                        '_type' => $type->getName()
                    ]
                ]
            );

        $options = ['notnull' => true, OroOptions::KEY => ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]];
        $column = $this->table->addColumn(self::COLUMN_NAME, 'string', $options);
        static::assertInstanceOf(ExtendColumn::class, $column);
        static::assertTrue($column->getNotnull());
    }

    private function setExpectations($name, string $extend, $expected): void
    {
        $type = Type::getType($name);

        $this->extendOptionsManager->expects(static::exactly(3))
            ->method('setColumnOptions')
            ->withConsecutive(
                [
                    self::TABLE_NAME,
                    self::COLUMN_NAME,
                    [
                        'extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'is_extend' => true],
                        '_type' => $type->getName()
                    ]
                ],
                [
                    self::TABLE_NAME,
                    self::COLUMN_NAME,
                    [
                        'extend' => [$extend => $expected],
                        '_type' => $type->getName()
                    ]
                ],
                [
                    self::TABLE_NAME,
                    self::COLUMN_NAME,
                    [
                        'extend' => ['nullable' => true],
                        '_type' => $type->getName()
                    ]
                ]
            );
    }
}
