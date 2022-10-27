<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\ExtendedAssociationFilter;
use Oro\Bundle\ApiBundle\Filter\ExtendedAssociationFilterFactory;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Provider\ExtendedAssociationProvider;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

class ExtendedAssociationFilterFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ValueNormalizer|\PHPUnit\Framework\MockObject\MockObject */
    private $valueNormalizer;

    /** @var ExtendedAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendedAssociationProvider;

    /** @var EntityOverrideProviderRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $entityOverrideProviderRegistry;

    /** @var ExtendedAssociationFilterFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->extendedAssociationProvider = $this->createMock(ExtendedAssociationProvider::class);
        $this->entityOverrideProviderRegistry = $this->createMock(EntityOverrideProviderRegistry::class);

        $this->factory = new ExtendedAssociationFilterFactory(
            $this->valueNormalizer,
            $this->extendedAssociationProvider,
            $this->entityOverrideProviderRegistry
        );
    }

    public function testCreateFilter()
    {
        $dataType = 'integer';

        $expectedFilter = new ExtendedAssociationFilter($dataType);
        $expectedFilter->setValueNormalizer($this->valueNormalizer);
        $expectedFilter->setExtendedAssociationProvider($this->extendedAssociationProvider);
        $expectedFilter->setEntityOverrideProviderRegistry($this->entityOverrideProviderRegistry);

        self::assertEquals(
            $expectedFilter,
            $this->factory->createFilter($dataType)
        );
    }
}
