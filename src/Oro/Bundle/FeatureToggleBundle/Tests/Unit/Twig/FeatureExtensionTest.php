<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Twig;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Twig\FeatureExtension;

class FeatureExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $featureChecker;

    /**
     * @var FeatureExtension
     */
    protected $featureExtension;

    protected function setUp()
    {
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->featureExtension = new FeatureExtension($this->featureChecker);
    }

    public function testGetFunctions()
    {
        $functions = $this->featureExtension->getFunctions();
        $this->assertArrayHasKey('feature_enabled', $functions);
        $this->assertArrayHasKey('feature_resource_enabled', $functions);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_feature_extension', $this->featureExtension->getName());
    }

    public function testIsFeatureEnabled()
    {
        $feature = 'test';
        $scopeIdentifier = 1;

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with($feature, $scopeIdentifier)
            ->willReturn(true);

        $this->assertTrue($this->featureExtension->isFeatureEnabled($feature, $scopeIdentifier));
    }

    public function testIsResourceEnabled()
    {
        $resource = 'resource';
        $resourceType = 'type';
        $scopeIdentifier = 2;

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($resource, $resourceType, $scopeIdentifier)
            ->willReturn(true);

        $this->assertTrue($this->featureExtension->isResourceEnabled($resource, $resourceType, $scopeIdentifier));
    }
}
