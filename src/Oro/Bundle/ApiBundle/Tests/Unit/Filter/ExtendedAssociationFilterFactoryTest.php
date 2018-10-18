<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\ExtendedAssociationFilter;
use Oro\Bundle\ApiBundle\Filter\ExtendedAssociationFilterFactory;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;

class ExtendedAssociationFilterFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AssociationManager */
    private $associationManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityOverrideProviderRegistry */
    private $entityOverrideProviderRegistry;

    /** @var ExtendedAssociationFilterFactory */
    private $factory;

    protected function setUp()
    {
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->associationManager = $this->createMock(AssociationManager::class);
        $this->entityOverrideProviderRegistry = $this->createMock(EntityOverrideProviderRegistry::class);

        $this->factory = new ExtendedAssociationFilterFactory(
            $this->valueNormalizer,
            $this->associationManager,
            $this->entityOverrideProviderRegistry
        );
    }

    public function testCreateFilter()
    {
        $dataType = 'integer';

        $expectedFilter = new ExtendedAssociationFilter($dataType);
        $expectedFilter->setValueNormalizer($this->valueNormalizer);
        $expectedFilter->setAssociationManager($this->associationManager);
        $expectedFilter->setEntityOverrideProviderRegistry($this->entityOverrideProviderRegistry);

        self::assertEquals(
            $expectedFilter,
            $this->factory->createFilter($dataType)
        );
    }
}
