<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\CompositeIdentifierFilter;
use Oro\Bundle\ApiBundle\Filter\CompositeIdentifierFilterFactory;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;

class CompositeIdentifierFilterFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdTransformerRegistry */
    private $entityIdTransformerRegistry;

    /** @var CompositeIdentifierFilterFactory */
    private $factory;

    protected function setUp()
    {
        $this->entityIdTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);

        $this->factory = new CompositeIdentifierFilterFactory(
            $this->entityIdTransformerRegistry
        );
    }

    public function testCreateFilter()
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
