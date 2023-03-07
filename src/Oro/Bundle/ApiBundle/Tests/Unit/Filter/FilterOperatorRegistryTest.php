<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;

class FilterOperatorRegistryTest extends \PHPUnit\Framework\TestCase
{
    private FilterOperatorRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new FilterOperatorRegistry([
            'eq'                 => '=',
            'without_short_name' => null
        ]);
    }

    /**
     * @dataProvider resolveOperatorDataProvider
     */
    public function testResolveOperator(string $operator, string $resolvedOperator): void
    {
        self::assertEquals($resolvedOperator, $this->registry->resolveOperator($operator));
    }

    public function resolveOperatorDataProvider(): array
    {
        return [
            ['=', 'eq'],
            ['eq', 'eq'],
            ['without_short_name', 'without_short_name']
        ];
    }

    public function testResolveOperatorForUnknownOperator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The operator "another" is not known.');
        $this->registry->resolveOperator('another');
    }
}
