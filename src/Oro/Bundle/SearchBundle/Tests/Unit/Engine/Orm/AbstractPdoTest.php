<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine\Orm;

use Oro\Bundle\SearchBundle\Engine\Orm\BaseDriver;
use Oro\Bundle\SearchBundle\Exception\ExpressionSyntaxError;
use Oro\Bundle\SearchBundle\Query\Query;
use PHPUnit\Framework\TestCase;

abstract class AbstractPdoTest extends TestCase
{
    protected BaseDriver $driver;

    public function testAddFilteringFieldException(): void
    {
        $this->expectException(ExpressionSyntaxError::class);
        $this->expectExceptionMessage('Unsupported operator "test"');

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
     */
    public function testAddFilteringField(string $condition, string $expected): void
    {
        $searchCondition = [
            'condition' => $condition,
            'fieldType' => Query::TYPE_INTEGER,
        ];

        $this->assertEquals($expected, $this->driver->addFilteringField(42, $searchCondition));
    }

    public function addFilteringFieldProvider(): array
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
