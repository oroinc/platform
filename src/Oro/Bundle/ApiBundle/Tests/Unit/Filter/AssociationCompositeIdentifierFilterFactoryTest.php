<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\AssociationCompositeIdentifierFilter;
use Oro\Bundle\ApiBundle\Filter\AssociationCompositeIdentifierFilterFactory;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;

class AssociationCompositeIdentifierFilterFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityIdTransformerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $idTransformerRegistry;

    /** @var AssociationCompositeIdentifierFilterFactory */
    private $factory;

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
