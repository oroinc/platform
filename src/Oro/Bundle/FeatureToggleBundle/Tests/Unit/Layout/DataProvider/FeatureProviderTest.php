<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Layout\DataProvider\FeatureProvider;

class FeatureProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $featureChecker;

    /**
     * @var FeatureProvider
     */
    protected $dataProvider;

    protected function setUp()
    {
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataProvider = new FeatureProvider($this->featureChecker);
    }

    public function testIsFeatureEnabled()
    {
        $feature = 'test';
        $scopeIdentifier = 1;

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with($feature, $scopeIdentifier)
            ->willReturn(true);

        $this->assertTrue($this->dataProvider->isFeatureEnabled($feature, $scopeIdentifier));
    }

    public function testIsResourceEnabled()
    {
        $resource = 'res';
        $resourceType = 'type';
        $scopeIdentifier = 1;

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($resource, $resourceType, $scopeIdentifier)
            ->willReturn(true);

        $this->assertTrue($this->dataProvider->isResourceEnabled($resource, $resourceType, $scopeIdentifier));
    }
}
