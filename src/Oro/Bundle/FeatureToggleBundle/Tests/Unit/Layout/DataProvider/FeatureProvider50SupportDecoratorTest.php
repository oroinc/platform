<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\FeatureToggleBundle\Layout\DataProvider\FeatureProvider;
use Oro\Bundle\FeatureToggleBundle\Layout\DataProvider\FeatureProvider50SupportDecorator;

class FeatureProvider50SupportDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $featureProvider;

    private FeatureProvider50SupportDecorator $dataProviderDecorator;

    protected function setUp(): void
    {
        $this->featureProvider = $this->createMock(FeatureProvider::class);

        $this->dataProviderDecorator = new FeatureProvider50SupportDecorator($this->featureProvider);
    }

    public function testIsFeatureEnabledFor50Theme()
    {
        $feature = 'test';
        $scopeIdentifier = 'string';

        $this->featureProvider->expects($this->once())
            ->method('isFeatureEnabled')
            ->with($feature, null)
            ->willReturn(true);

        $this->assertTrue($this->dataProviderDecorator->isFeatureEnabled($feature, $scopeIdentifier));
    }

    public function testIsFeatureEnabled()
    {
        $feature = 'test';
        $scopeIdentifier = 1;

        $this->featureProvider->expects($this->once())
            ->method('isFeatureEnabled')
            ->with($feature, $scopeIdentifier)
            ->willReturn(true);

        $this->assertTrue($this->dataProviderDecorator->isFeatureEnabled($feature, $scopeIdentifier));
    }

    public function testIsResourceEnabled()
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
