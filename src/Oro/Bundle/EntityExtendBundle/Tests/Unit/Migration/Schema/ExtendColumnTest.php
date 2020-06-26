<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration\Schema;

use Doctrine\DBAL\Schema\Column as BaseColumn;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendColumn;
use PHPUnit\Framework\MockObject\MockObject;

class ExtendColumnTest extends \PHPUnit\Framework\TestCase
{
    const TABLE_NAME = 'test_table';
    const COLUMN_NAME = 'test_name';

    /** @var ExtendOptionsManager|MockObject */
    protected $extendOptionsManager;

    /** @var ExtendColumn */
    protected $column;

    protected function setUp(): void
    {
        $this->extendOptionsManager = $this->createMock(ExtendOptionsManager::class);

        $this->column = new ExtendColumn([
            'extendOptionsManager' => $this->extendOptionsManager,
            'tableName' => self::TABLE_NAME,
            'column' => new BaseColumn(self::COLUMN_NAME, Type::getType('string')),
        ]);
    }

    public function testSetOptions()
    {
        $options = [
            OroOptions::KEY => ['key1' => 'value1'],
            'key2' => 'value2',
            'type' => Type::getType('integer')
        ];

        $this->extendOptionsManager->expects(static::exactly(2))
            ->method('setColumnOptions')
            ->withConsecutive(
                [self::TABLE_NAME, self::COLUMN_NAME, $options[OroOptions::KEY]],
                [self::TABLE_NAME, self::COLUMN_NAME, ['_type' => 'integer']]
            );

        static::assertEquals(Type::getType('string'), $this->column->getType());
        $this->column->setOptions($options);
        static::assertEquals(Type::getType('integer'), $this->column->getType());
    }

    public function testSetType()
    {
        $this->extendOptionsManager->expects(static::once())
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, [
                ExtendOptionsManager::TYPE_OPTION => 'integer'
            ]);
        static::assertEquals(Type::getType('string'), $this->column->getType());
        $this->column->setType(Type::getType('integer'));
        static::assertEquals(Type::getType('integer'), $this->column->getType());
    }

    public function testSetLength()
    {
        $this->extendOptionsManager->expects(static::once())
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, [
                'extend' => ['length' => 100],
                ExtendOptionsManager::TYPE_OPTION => 'string'
            ]);
        static::assertNull($this->column->getLength());
        $this->column->setLength(100);
        static::assertEquals(100, $this->column->getLength());
    }

    public function testSetPrecision()
    {
        $this->extendOptionsManager->expects(static::once())
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, [
                'extend' => ['precision' => 8],
                ExtendOptionsManager::TYPE_OPTION => 'string'
            ]);
        static::assertEquals(10, $this->column->getPrecision());
        $this->column->setPrecision(8);
        static::assertEquals(8, $this->column->getPrecision());
    }

    public function testSetScale()
    {
        $this->extendOptionsManager->expects(static::once())
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, [
                'extend' => ['scale' => 5],
                ExtendOptionsManager::TYPE_OPTION => 'string'
            ]);
        static::assertEquals(0, $this->column->getScale());
        $this->column->setScale(5);
        static::assertEquals(5, $this->column->getScale());
    }

    public function testSetDefault()
    {
        $this->extendOptionsManager->expects(static::once())
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, [
                'extend' => ['default' => true],
                ExtendOptionsManager::TYPE_OPTION => 'string'
            ]);
        static::assertNull($this->column->getDefault());
        $this->column->setDefault(true);
        static::assertTrue($this->column->getDefault());
    }

    public function testSetNotNull()
    {
        $this->extendOptionsManager->expects(static::once())
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, [
                'extend' => ['nullable' => true],
                ExtendOptionsManager::TYPE_OPTION => 'string'
            ]);
        static::assertTrue($this->column->getNotnull());
        $this->column->setNotnull(false);
        static::assertFalse($this->column->getNotnull());
    }
}
