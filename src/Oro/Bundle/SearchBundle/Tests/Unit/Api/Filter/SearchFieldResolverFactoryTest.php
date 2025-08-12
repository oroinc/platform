<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api\Filter;

use Oro\Bundle\SearchBundle\Api\Filter\SearchFieldResolver;
use Oro\Bundle\SearchBundle\Api\Filter\SearchFieldResolverFactory;
use Oro\Bundle\SearchBundle\Api\SearchMappingProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchFieldResolverFactoryTest extends TestCase
{
    private SearchMappingProvider&MockObject $searchMappingProvider;
    private SearchFieldResolverFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->searchMappingProvider = $this->createMock(SearchMappingProvider::class);

        $this->factory = new SearchFieldResolverFactory($this->searchMappingProvider);
    }

    public function testCreateFilter(): void
    {
        $entityClass = 'Test\Entity';
        $fieldMappings = ['field1' => 'field_1'];
        $generalFieldMappings = ['field2' => 'field_2'];
        $expectedFieldMappings = ['field2' => 'field_2', 'field1' => 'field_1'];
        $searchFieldTypes = [
            'field1' => 'integer',
            'field2' => 'datetime',
            'field3' => 'text',
            'all_text' => 'text'
        ];
        $searchFieldMappings = [
            'field_1' => ['type' => 'integer'],
            'field_2' => ['type' => 'datetime'],
            'field3' => ['type' => 'text'],
            'all_text' => ['type' => 'text']
        ];

        $this->searchMappingProvider->expects(self::once())
            ->method('getSearchFieldTypes')
            ->with($entityClass)
            ->willReturn($searchFieldTypes);
        $this->searchMappingProvider->expects(self::once())
            ->method('getFieldMappings')
            ->with($entityClass)
            ->willReturn($generalFieldMappings);

        self::assertEquals(
            new SearchFieldResolver($searchFieldMappings, $expectedFieldMappings),
            $this->factory->createFieldResolver($entityClass, $fieldMappings)
        );
    }

    public function testCreateFilterForUnknownEntity(): void
    {
        $entityClass = 'Test\Entity';
        $fieldMappings = ['apiField1' => 'field1'];

        $this->searchMappingProvider->expects(self::once())
            ->method('getSearchFieldTypes')
            ->with($entityClass)
            ->willReturn([]);

        $expectedFieldResolver = new SearchFieldResolver([], $fieldMappings);

        self::assertEquals(
            $expectedFieldResolver,
            $this->factory->createFieldResolver($entityClass, $fieldMappings)
        );
    }
}
