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

    public function testGetFunctions()
    {
        $functions = $this->featureExtension->getFunctions();
        $this->assertCount(2, $functions);
        $expectedFunctions = ['feature_enabled', 'feature_resource_enabled'];
        /** @var \Twig_SimpleFunction[] $functions */
        foreach ($functions as $function) {
            $this->assertInstanceOf(\Twig_SimpleFunction::class, $function);
            $this->assertContains($function->getName(), $expectedFunctions);
        }
    }

    public function testGetName()
    {
        $this->assertEquals('oro_featuretoggle_extension', $this->featureExtension->getName());
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

    protected function setUp()
    {
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->featureExtension = new FeatureExtension($this->featureChecker);
    }
}
