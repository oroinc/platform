<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\AssociationCompositeIdentifierFilter;
use Oro\Bundle\ApiBundle\Filter\AssociationCompositeIdentifierFilterFactory;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AssociationCompositeIdentifierFilterFactoryTest extends TestCase
{
    private EntityIdTransformerRegistry&MockObject $idTransformerRegistry;
    private AssociationCompositeIdentifierFilterFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->idTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);

        $this->factory = new AssociationCompositeIdentifierFilterFactory($this->idTransformerRegistry);
    }

    public function testCreateFilter(): void
    {
        $expectedFilter = new AssociationCompositeIdentifierFilter(DataType::STRING);
        $expectedFilter->setEntityIdTransformerRegistry($this->idTransformerRegistry);

        self::assertEquals(
            $expectedFilter,
            $this->factory->createFilter(DataType::STRING)
        );
    }
}
