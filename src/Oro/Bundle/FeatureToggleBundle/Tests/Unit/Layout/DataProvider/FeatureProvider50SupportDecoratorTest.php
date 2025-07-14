<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\FeatureToggleBundle\Layout\DataProvider\FeatureProvider;
use Oro\Bundle\FeatureToggleBundle\Layout\DataProvider\FeatureProvider50SupportDecorator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeatureProvider50SupportDecoratorTest extends TestCase
{
    private FeatureProvider&MockObject $featureProvider;
    private FeatureProvider50SupportDecorator $dataProviderDecorator;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureProvider = $this->createMock(FeatureProvider::class);

        $this->dataProviderDecorator = new FeatureProvider50SupportDecorator($this->featureProvider);
    }

    public function testIsFeatureEnabledFor50Theme(): void
    {
        $feature = 'test';
        $scopeIdentifier = 'string';

        $this->featureProvider->expects($this->once())
            ->method('isFeatureEnabled')
            ->with($feature, null)
            ->willReturn(true);

        $this->assertTrue($this->dataProviderDecorator->isFeatureEnabled($feature, $scopeIdentifier));
    }

    public function testIsFeatureEnabled(): void
    {
        $feature = 'test';
        $scopeIdentifier = 1;

        $this->featureProvider->expects($this->once())
            ->method('isFeatureEnabled')
            ->with($feature, $scopeIdentifier)
            ->willReturn(true);

        $this->assertTrue($this->dataProviderDecorator->isFeatureEnabled($feature, $scopeIdentifier));
    }

    public function testIsResourceEnabled(): void
    {
        $resource = 'res';
        $resourceType = 'type';
        $scopeIdentifier = 1;

        $this->featureProvider->expects($this->once())
            ->method('isResourceEnabled')
            ->with($resource, $resourceType, $scopeIdentifier)
            ->willReturn(true);

        $this->assertTrue($this->dataProviderDecorator->isResourceEnabled($resource, $resourceType, $scopeIdentifier));
    }
}
