<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\SearchFieldResolver;
use Oro\Bundle\ApiBundle\Filter\SearchFieldResolverFactory;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;

class SearchFieldResolverFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|AbstractSearchMappingProvider */
    private $searchMappingProvider;

    /** @var SearchFieldResolverFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->searchMappingProvider = $this->createMock(AbstractSearchMappingProvider::class);

        $this->factory = new SearchFieldResolverFactory(
            $this->searchMappingProvider
        );
    }

    public function testCreateFilter()
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
