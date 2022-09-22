<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\AssociationCompositeIdFilter;
use Oro\Bundle\ApiBundle\Filter\AssociationCompositeIdFilterFactory;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AssociationCompositeIdFilterFactoryTest extends TestCase
{
    private MockObject|EntityIdTransformerRegistry $idTransformerRegistry;

    private AssociationCompositeIdFilterFactory $factory;

    protected function setUp(): void
    {
        $this->idTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);

        $this->factory = new AssociationCompositeIdFilterFactory($this->idTransformerRegistry);
    }

    public function testCreateFilter(): void
    {
        $expectedFilter = new AssociationCompositeIdFilter(DataType::STRING);
        $expectedFilter->setEntityIdTransformerRegistry($this->idTransformerRegistry);

        self::assertEquals(
            $expectedFilter,
            $this->factory->createFilter(DataType::STRING)
        );
    }
}
