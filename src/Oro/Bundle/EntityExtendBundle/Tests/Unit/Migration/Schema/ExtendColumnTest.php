<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration\Schema;

use Doctrine\DBAL\Schema\Column as BaseColumn;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendColumn;

class ExtendColumnTest extends \PHPUnit\Framework\TestCase
{
    private const TABLE_NAME = 'test_table';
    private const COLUMN_NAME = 'test_name';

    /** @var ExtendOptionsManager|\PHPUnit\Framework\MockObject\MockObject */
    private $extendOptionsManager;

    /** @var ExtendColumn */
    private $column;

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

        $this->extendOptionsManager->expects(self::exactly(2))
            ->method('setColumnOptions')
            ->withConsecutive(
                [self::TABLE_NAME, self::COLUMN_NAME, $options[OroOptions::KEY]],
                [self::TABLE_NAME, self::COLUMN_NAME, ['_type' => 'integer']]
            );

        self::assertEquals(Type::getType('string'), $this->column->getType());
        $this->column->setOptions($options);
        self::assertEquals(Type::getType('integer'), $this->column->getType());
    }

    public function testSetType()
    {
        $this->extendOptionsManager->expects(self::once())
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, [
                ExtendOptionsManager::TYPE_OPTION => 'integer'
            ]);
        self::assertEquals(Type::getType('string'), $this->column->getType());
        $this->column->setType(Type::getType('integer'));
        self::assertEquals(Type::getType('integer'), $this->column->getType());
    }

    public function testSetLength()
    {
        $this->extendOptionsManager->expects(self::once())
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, [
                'extend' => ['length' => 100],
                ExtendOptionsManager::TYPE_OPTION => 'string'
            ]);
        self::assertNull($this->column->getLength());
        $this->column->setLength(100);
        self::assertEquals(100, $this->column->getLength());
    }

    public function testSetPrecision()
    {
        $this->extendOptionsManager->expects(self::once())
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, [
                'extend' => ['precision' => 8],
                ExtendOptionsManager::TYPE_OPTION => 'string'
            ]);
        self::assertEquals(10, $this->column->getPrecision());
        $this->column->setPrecision(8);
        self::assertEquals(8, $this->column->getPrecision());
    }

    public function testSetScale()
    {
        $this->extendOptionsManager->expects(self::once())
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, [
                'extend' => ['scale' => 5],
                ExtendOptionsManager::TYPE_OPTION => 'string'
            ]);
        self::assertEquals(0, $this->column->getScale());
        $this->column->setScale(5);
        self::assertEquals(5, $this->column->getScale());
    }

    public function testSetDefault()
    {
        $this->extendOptionsManager->expects(self::once())
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, [
                'extend' => ['default' => true],
                ExtendOptionsManager::TYPE_OPTION => 'string'
            ]);
        self::assertNull($this->column->getDefault());
        $this->column->setDefault(true);
        self::assertTrue($this->column->getDefault());
    }

    public function testSetNotNull()
    {
        $this->extendOptionsManager->expects(self::once())
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, [
                'extend' => ['nullable' => true],
                ExtendOptionsManager::TYPE_OPTION => 'string'
            ]);
        self::assertTrue($this->column->getNotnull());
        $this->column->setNotnull(false);
        self::assertFalse($this->column->getNotnull());
    }
}
