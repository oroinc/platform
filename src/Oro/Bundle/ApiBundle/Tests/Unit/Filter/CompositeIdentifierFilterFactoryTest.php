<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\CompositeIdentifierFilter;
use Oro\Bundle\ApiBundle\Filter\CompositeIdentifierFilterFactory;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompositeIdentifierFilterFactoryTest extends TestCase
{
    private EntityIdTransformerRegistry&MockObject $entityIdTransformerRegistry;
    private CompositeIdentifierFilterFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityIdTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);

        $this->factory = new CompositeIdentifierFilterFactory(
            $this->entityIdTransformerRegistry
        );
    }

    public function testCreateFilter(): void
    {
        $dataType = 'string';

        $expectedFilter = new CompositeIdentifierFilter($dataType);
        $expectedFilter->setEntityIdTransformerRegistry($this->entityIdTransformerRegistry);

        self::assertEquals(
            $expectedFilter,
            $this->factory->createFilter($dataType)
        );
    }
}
