<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api\Processor\MultiTargetSearch;

use Oro\Bundle\SearchBundle\Api\Processor\MultiTargetSearch\MultiTargetSearchMappingProvider;
use Oro\Bundle\SearchBundle\Api\SearchMappingProvider;
use PHPUnit\Framework\TestCase;

class MultiTargetSearchMappingProviderTest extends TestCase
{
    public function testGetSearchFieldMappings(): void
    {
        $entity1SearchFieldTypes = [
            'field1' => 'integer',
            'field2' => 'datetime',
            'field3' => 'text',
        ];
        $entity2SearchFieldTypes = [
            'field2' => 'datetime',
            'field3' => 'text',
        ];
        $entity3SearchFieldTypes = [
            'field1' => 'integer',
            'field4' => 'text',
            'field5' => 'text',
        ];

        $fieldMappings = [
            'field1' => 'search_field_1',
            'field2' => ['search_field_2_1', 'search_field_2_2'],
            'field3' => 'search_field_3',
            'field4' => 'search_field_3',
            'field5' => 'search_field_5',
        ];

        $searchMappingProvider = $this->createMock(SearchMappingProvider::class);
        $searchMappingProvider->expects(self::exactly(3))
            ->method('getSearchFieldTypes')
            ->willReturnMap([
                ['Test\Entity1', $entity1SearchFieldTypes],
                ['Test\Entity2', $entity2SearchFieldTypes],
                ['Test\Entity3', $entity3SearchFieldTypes],
            ]);

        $multiTargetSearchMappingProvider = new MultiTargetSearchMappingProvider($searchMappingProvider);
        $searchFieldMappings = $multiTargetSearchMappingProvider->getSearchFieldMappings(
            ['Test\Entity1', 'Test\Entity2', 'Test\Entity3'],
            $fieldMappings
        );
        self::assertEquals(
            [
                'search_field_1' => ['type' => 'integer'],
                'search_field_2_1' => ['type' => 'datetime'],
                'search_field_2_2' => ['type' => 'datetime'],
                'search_field_3' => ['type' => 'text'],
                'search_field_5' => ['type' => 'text'],
            ],
            $searchFieldMappings
        );
    }

    public function testGetFieldMappings(): void
    {
        $entity1FieldMappings = [
            'field1' => 'search_field_1',
            'field2' => 'search_field_2',
            'field3' => 'search_field_3',
        ];
        $entity2FieldMappings = [
            'field2' => 'search_field_2',
            'field3' => 'search_field_3_1',
            'field4' => 'search_field_4',
        ];
        $entity3FieldMappings = [
            'field2' => 'search_field_2',
            'field3' => 'search_field_3',
            'field5' => 'search_field_4',
        ];

        $searchMappingProvider = $this->createMock(SearchMappingProvider::class);
        $searchMappingProvider->expects(self::exactly(3))
            ->method('getFieldMappings')
            ->willReturnMap([
                ['Test\Entity1', $entity1FieldMappings],
                ['Test\Entity2', $entity2FieldMappings],
                ['Test\Entity3', $entity3FieldMappings],
            ]);

        $multiTargetSearchMappingProvider = new MultiTargetSearchMappingProvider($searchMappingProvider);
        $fieldMappings = $multiTargetSearchMappingProvider->getFieldMappings(
            ['Test\Entity1', 'Test\Entity2', 'Test\Entity3']
        );
        self::assertEquals(
            [
                'field1' => 'search_field_1',
                'field2' => 'search_field_2',
                'field3' => ['search_field_3', 'search_field_3_1'],
                'field4' => 'search_field_4',
                'field5' => 'search_field_4',
            ],
            $fieldMappings
        );
    }
}
