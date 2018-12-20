<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration\Schema;

use Doctrine\DBAL\Schema\Column as BaseColumn;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendColumn;

class ExtendColumnTest extends \PHPUnit\Framework\TestCase
{
    const TABLE_NAME = 'test_table';
    const COLUMN_NAME = 'test_name';

    /** @var ExtendOptionsManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $extendOptionsManager;

    /** @var ExtendColumn */
    protected $column;

    protected function setUp()
    {
        $this->extendOptionsManager = $this->createMock(ExtendOptionsManager::class);

        $this->column = new ExtendColumn(
            [
                'extendOptionsManager' => $this->extendOptionsManager,
                'tableName' => self::TABLE_NAME,
                'column' => new BaseColumn(self::COLUMN_NAME, Type::getType('string')),
            ]
        );
    }

    public function testSetOptions()
    {
        $options = [
            OroOptions::KEY => [
                'key1' => 'value1'
            ],
            'key2' => 'value2',
            'type' => Type::getType('integer')
        ];

        $this->extendOptionsManager->expects($this->at(0))
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, $options[OroOptions::KEY]);
        $this->extendOptionsManager->expects($this->at(1))
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, ['_type' => 'integer']);

        $this->assertAttributeEquals(Type::getType('string'), '_type', $this->column);

        $this->column->setOptions($options);

        $this->assertAttributeEquals(Type::getType('integer'), '_type', $this->column);
    }

    /**
     * @dataProvider setSomeOptionDataProvider
     *
     * @param string $name
     * @param string $method
     * @param array $options
     * @param mixed $initial
     * @param mixed $expected
     */
    public function testSetSomeOption($name, $method, array $options, $initial, $expected)
    {
        $this->extendOptionsManager->expects($this->once())
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, $options);

        $this->assertAttributeEquals($initial, $name, $this->column);

        $this->column->$method($expected);

        $this->assertAttributeEquals($expected, $name, $this->column);
    }

    /**
     * @return array
     */
    public function setSomeOptionDataProvider()
    {
        return [
            'type' => [
                'name' => ExtendOptionsManager::TYPE_OPTION,
                'method' => 'setType',
                'options' => [ExtendOptionsManager::TYPE_OPTION => 'integer'],
                'initial' => Type::getType('string'),
                'expected' => Type::getType('integer')
            ],
            'length' => [
                'name' => '_length',
                'method' => 'setLength',
                'options' => ['extend' => ['length' => 100], ExtendOptionsManager::TYPE_OPTION => 'string'],
                'initial' => null,
                'expected' => 100
            ],
            'precision' => [
                'name' => '_precision',
                'method' => 'setPrecision',
                'options' => ['extend' => ['precision' => 8], ExtendOptionsManager::TYPE_OPTION => 'string'],
                'initial' => 10,
                'expected' => 8
            ],
            'scale' => [
                'name' => '_scale',
                'method' => 'setScale',
                'options' => ['extend' => ['scale' => 5], ExtendOptionsManager::TYPE_OPTION => 'string'],
                'initial' => 0,
                'expected' => 5
            ],
            'default' => [
                'name' => '_default',
                'method' => 'setDefault',
                'options' => ['extend' => ['default' => true], ExtendOptionsManager::TYPE_OPTION => 'string'],
                'initial' => null,
                'expected' => true
            ],
        ];
    }
}
