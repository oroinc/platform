<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api\Filter;

use Oro\Bundle\SearchBundle\Api\Filter\SearchFieldResolver;
use Oro\Bundle\SearchBundle\Api\Filter\SearchFieldResolverFactory;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchFieldResolverFactoryTest extends TestCase
{
    private AbstractSearchMappingProvider&MockObject $searchMappingProvider;
    private SearchFieldResolverFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->searchMappingProvider = $this->createMock(AbstractSearchMappingProvider::class);

        $this->factory = new SearchFieldResolverFactory(
            $this->searchMappingProvider
        );
    }

    public function testCreateFilter(): void
    {
        $entityClass = 'Test\Entity';
        $fieldMappings = ['key1' => 'value1'];
        $mapping = ['fields' => ['key2' => 'value2']];

        $this->searchMappingProvider->expects(self::once())
            ->method('getEntityConfig')
            ->with($entityClass)
            ->willReturn($mapping);

        $expectedFieldResolver = new SearchFieldResolver($mapping['fields'], $fieldMappings);

        self::assertEquals(
            $expectedFieldResolver,
            $this->factory->createFieldResolver($entityClass, $fieldMappings)
        );
    }
}
