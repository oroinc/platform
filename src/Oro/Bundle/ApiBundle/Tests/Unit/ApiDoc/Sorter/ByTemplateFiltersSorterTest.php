<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Sorter;

use Oro\Bundle\ApiBundle\ApiDoc\Sorter\ByTemplateFiltersSorter;

class ByTemplateFiltersSorterTest extends \PHPUnit\Framework\TestCase
{
    private function getSorter(array $sortingTemplate, bool $addOtherFiltersToBegin): ByTemplateFiltersSorter
    {
        return new ByTemplateFiltersSorter($sortingTemplate, $addOtherFiltersToBegin);
    }

    public function testSortFiltersWhenOtherFiltersAddedToEnd()
    {
        $filters = [
            'fields[field2]' => ['key' => 'fields.field2'],
            'filter[field2]' => ['key' => 'filter.field2'],
            'other2'         => ['key' => 'other2'],
            'include'        => ['key' => 'include'],
            'filter[field1]' => ['key' => 'filter.field1'],
            'fields[field1]' => ['key' => 'fields.field1'],
            'other1'         => ['key' => 'other1']
        ];
        $expectedFilters = [
            'filter[field1]' => ['key' => 'filter.field1'],
            'filter[field2]' => ['key' => 'filter.field2'],
            'fields[field1]' => ['key' => 'fields.field1'],
            'fields[field2]' => ['key' => 'fields.field2'],
            'include'        => ['key' => 'include'],
            'other1'         => ['key' => 'other1'],
            'other2'         => ['key' => 'other2']
        ];

        $sorter = $this->getSorter(['filter', 'fields', 'include'], false);
        self::assertSame($expectedFilters, $sorter->sortFilters($filters));
    }

    public function testSortFiltersWhenOtherFiltersAddedToBegin()
    {
        $filters = [
            'fields[field2]' => ['key' => 'fields.field2'],
            'filter[field2]' => ['key' => 'filter.field2'],
            'other2'         => ['key' => 'other2'],
            'include'        => ['key' => 'include'],
            'filter[field1]' => ['key' => 'filter.field1'],
            'fields[field1]' => ['key' => 'fields.field1'],
            'other1'         => ['key' => 'other1']
        ];
        $expectedFilters = [
            'other1'         => ['key' => 'other1'],
            'other2'         => ['key' => 'other2'],
            'filter[field1]' => ['key' => 'filter.field1'],
            'filter[field2]' => ['key' => 'filter.field2'],
            'fields[field1]' => ['key' => 'fields.field1'],
            'fields[field2]' => ['key' => 'fields.field2'],
            'include'        => ['key' => 'include']
        ];

        $sorter = $this->getSorter(['filter', 'fields', 'include'], true);
        self::assertSame($expectedFilters, $sorter->sortFilters($filters));
    }
}
