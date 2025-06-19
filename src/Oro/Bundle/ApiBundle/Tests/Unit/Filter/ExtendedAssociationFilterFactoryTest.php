<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\ExtendedAssociationFilter;
use Oro\Bundle\ApiBundle\Filter\ExtendedAssociationFilterFactory;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Provider\ExtendedAssociationProvider;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExtendedAssociationFilterFactoryTest extends TestCase
{
    private ValueNormalizer&MockObject $valueNormalizer;
    private ExtendedAssociationProvider&MockObject $extendedAssociationProvider;
    private EntityOverrideProviderRegistry&MockObject $entityOverrideProviderRegistry;
    private ExtendedAssociationFilterFactory $factory;

    #[\Override]
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

    public function testCreateFilter(): void
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
