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
use PHPUnit\Framework\TestCase;

class ExtendTableTest extends TestCase
{
    const TABLE_NAME = 'test_table';
    const COLUMN_NAME = 'test_column';

    /** @var ExtendOptionsManager|\PHPUnit\Framework\MockObject\MockObject */
    private $extendOptionsManager;

    /** @var DbIdentifierNameGenerator|\PHPUnit\Framework\MockObject\MockObject */
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

    /**
     * @dataProvider addColumnWithSameExtendDataProvider
     *
     * @param Type $type
     * @param array $options
     * @param string $attribute
     * @param string $extend
     * @param mixed $expected
     */
    public function testAddColumnWithSameExtend(Type $type, array $options, $attribute, $extend, $expected)
    {
        $options[OroOptions::KEY]['extend'] = ['owner' => ExtendScope::OWNER_CUSTOM];

        $this->extendOptionsManager->expects($this->at(0))
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'is_extend' => true,
                ],
                '_type' => $type->getName()
            ]);

        $this->extendOptionsManager->expects($this->at(1))
            ->method('setColumnOptions')
            ->with(self::TABLE_NAME, self::COLUMN_NAME, [
                'extend' => [
                    $extend => $expected
                ],
                '_type' => $type->getName()
            ]);

        /** @var ExtendColumn $column */
        $column = $this->table->addColumn(self::COLUMN_NAME, $type->getName(), $options);
        $this->assertInstanceOf(ExtendColumn::class, $column);

        $this->assertAttributeEquals($expected, $attribute, $column);
    }

    /**
     * @return array
     */
    public function addColumnWithSameExtendDataProvider()
    {
        return [
            'length' => [
                'type' => Type::getType('string'),
                'options' => ['length' => 100],
                'attribute' => '_length',
                'extend' => 'length',
                'expected' => 100,
            ],
            'precision' => [
                'type' => Type::getType('float'),
                'options' => ['precision' => 8],
                'attribute' => '_precision',
                'extend' => 'precision',
                'expected' => 8,
            ],
            'scale' => [
                'type' => Type::getType('float'),
                'options' => ['scale' => 5],
                'attribute' => '_scale',
                'extend' => 'scale',
                'expected' => 5,
            ],
            'default' => [
                'type' => Type::getType('string'),
                'options' => ['default' => 'N/A'],
                'attribute' => '_default',
                'extend' => 'default',
                'expected' => 'N/A',
            ],
        ];
    }
}
