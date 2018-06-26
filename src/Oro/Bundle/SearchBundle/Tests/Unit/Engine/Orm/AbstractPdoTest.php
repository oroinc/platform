<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine\Orm;

use Oro\Bundle\SearchBundle\Engine\Orm\BaseDriver;
use Oro\Bundle\SearchBundle\Query\Query;

abstract class AbstractPdoTest extends \PHPUnit\Framework\TestCase
{
    const JOIN_ALIAS = 'item';

    /** @var BaseDriver */
    protected $driver;

    /**
     * @expectedException \Oro\Bundle\SearchBundle\Exception\ExpressionSyntaxError
     * @expectedExceptionMessage Unsupported operator "test"
     */
    public function testAddFilteringFieldException()
    {
        $this->driver->addFilteringField(
            42,
            [
                'condition' => 'test',
                'fieldType' => Query::TYPE_INTEGER,
                'fieldName' => 'field'
            ]
        );
    }

    /**
     * @dataProvider addFilteringFieldProvider
     *
     * @param string $condition
     * @param string $expected
     */
    public function testAddFilteringField($condition, $expected)
    {
        $searchCondition = [
            'condition' => $condition,
            'fieldType' => Query::TYPE_INTEGER,
        ];

        $this->assertEquals($expected, $this->driver->addFilteringField(42, $searchCondition));
    }

    /**
     * @return array
     */
    public function addFilteringFieldProvider()
    {
        return [
            Query::OPERATOR_EXISTS => [
                'condition' => Query::OPERATOR_EXISTS,
                'expected' => 'integerField42.id IS NOT NULL',
            ],
            Query::OPERATOR_NOT_EXISTS => [
                'condition' => Query::OPERATOR_NOT_EXISTS,
                'expected' => 'integerField42.id IS NULL',
            ],
        ];
    }
}
