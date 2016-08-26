<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Layout\Block;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Layout\Block\FeatureToggleExtension;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class FeatureToggleExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $featureChecker;

    /**
     * @var FeatureToggleExtension
     */
    protected $featureToggleExtension;

    protected function setUp()
    {
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->featureToggleExtension = new FeatureToggleExtension($this->featureChecker);
    }

    /**
     * @dataProvider  isFeatureEnabledDataProvider
     * @param $featureEnable
     * @param $initVisible
     * @param $visible
     */
    public function testIsFeatureEnabled($featureEnable, $initVisible, $visible)
    {
        $feature = 'test';
        $scopeIdentifier = 1;

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with($feature, $scopeIdentifier)
            ->willReturn($featureEnable);

        $view = new BlockView();
        $view->vars['visible'] = $initVisible;
        /** @var BlockInterface|\PHPUnit_Framework_MockObject_MockObject $block */
        $block = $this->getMock(BlockInterface::class);

        $options = [
            'feature' => ['name' => $feature, 'scope' => $scopeIdentifier]
        ];
        $this->featureToggleExtension->finishView($view, $block, $options);

        $this->assertEquals($visible, $view->vars['visible']);
    }

    /**
     * @return array
     */
    public function isFeatureEnabledDataProvider()
    {
        return [
            ['featureEnable' => true, 'initVisible' => true, 'visible' => true],
            ['featureEnable' => true, 'initVisible' => false, 'visible' => false],
            ['featureEnable' => false, 'initVisible' => true, 'visible' => false],
            ['featureEnable' => false, 'initVisible' => false, 'visible' => false],
        ];
    }

    /**
     * @dataProvider  isFeatureEnabledDataProvider
     * @param bool $resourceEnable
     * @param bool $initVisible
     * @param bool $visible
     */
    public function testIsResourceEnabled($resourceEnable, $initVisible, $visible)
    {
        $resource = 'res';
        $resourceType = 'type';
        $scopeIdentifier = 1;

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($resource, $resourceType, $scopeIdentifier)
            ->willReturn($resourceEnable);

        $view = new BlockView();
        $view->vars['visible'] = $initVisible;
        /** @var BlockInterface|\PHPUnit_Framework_MockObject_MockObject $block */
        $block = $this->getMock(BlockInterface::class);

        $options = [
            'feature' => ['resource' => $resource, 'type' => $resourceType, 'scope' => $scopeIdentifier]
        ];
        $this->featureToggleExtension->finishView($view, $block, $options);
        $this->assertEquals($visible, $view->vars['visible']);
    }

    /**
     * @return array
     */
    public function isResourceEnabledProvider()
    {
        return [
            ['resourceEnable' => true, 'initVisible' => true, 'visible' => true],
            ['resourceEnable' => true, 'initVisible' => false, 'visible' => false],
            ['resourceEnable' => false, 'initVisible' => true, 'visible' => false],
            ['resourceEnable' => false, 'initVisible' => false, 'visible' => false],
        ];
    }
}
