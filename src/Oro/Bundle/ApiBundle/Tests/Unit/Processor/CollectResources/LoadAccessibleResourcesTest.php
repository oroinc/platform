<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResources\LoadAccessibleResources;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Request\ApiResource;

class LoadAccessibleResourcesTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityOverrideProviderRegistry */
    private $entityOverrideProviderRegistry;

    /** @var LoadAccessibleResources */
    private $processor;

    protected function setUp()
    {
        $this->entityOverrideProviderRegistry = $this->createMock(EntityOverrideProviderRegistry::class);

        $this->processor = new LoadAccessibleResources($this->entityOverrideProviderRegistry);
    }

    public function testProcessWhenAccessibleResourcesAreAlreadyBuilt()
    {
        $context = new CollectResourcesContext();
        $context->setAccessibleResources(['Test\Entity']);

        $this->entityOverrideProviderRegistry->expects(self::never())
            ->method('getEntityOverrideProvider');

        $this->processor->process($context);

        self::assertEquals(['Test\Entity'], $context->getAccessibleResources());
    }

    public function testProcessAccessibleResource()
    {
        $context = new CollectResourcesContext();

        $resources = $context->getResult();
        $resources->add(new ApiResource('Test\Entity'));

        $entityOverrideProvider = $this->createMock(EntityOverrideProviderInterface::class);
        $this->entityOverrideProviderRegistry->expects(self::any())
            ->method('getEntityOverrideProvider')
            ->willReturn($entityOverrideProvider);
        $entityOverrideProvider->expects(self::once())
            ->method('getSubstituteEntityClass')
            ->with('Test\Entity')
            ->willReturn(null);

        $this->processor->process($context);

        self::assertEquals(['Test\Entity'], $context->getAccessibleResources());
    }

    public function testProcessForNotAccessibleResourceBecauseGetActionIsExcluded()
    {
        $context = new CollectResourcesContext();

        $resources = $context->getResult();
        $resources->add(new ApiResource('Test\Entity'));
        $resources->get('Test\Entity')->setExcludedActions(['get']);

        $entityOverrideProvider = $this->createMock(EntityOverrideProviderInterface::class);
        $this->entityOverrideProviderRegistry->expects(self::any())
            ->method('getEntityOverrideProvider')
            ->willReturn($entityOverrideProvider);
        $entityOverrideProvider->expects(self::never())
            ->method('getSubstituteEntityClass');

        $this->processor->process($context);

        self::assertEquals([], $context->getAccessibleResources());
    }

    public function testProcessForNotAccessibleResourceBecauseItIsOverridden()
    {
        $context = new CollectResourcesContext();

        $resources = $context->getResult();
        $resources->add(new ApiResource('Test\Entity'));

        $entityOverrideProvider = $this->createMock(EntityOverrideProviderInterface::class);
        $this->entityOverrideProviderRegistry->expects(self::any())
            ->method('getEntityOverrideProvider')
            ->willReturn($entityOverrideProvider);
        $entityOverrideProvider->expects(self::once())
            ->method('getSubstituteEntityClass')
            ->with('Test\Entity')
            ->willReturn('Test\AnotherEntity');

        $this->processor->process($context);

        self::assertEquals([], $context->getAccessibleResources());
    }
}
