<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Event\FeatureChange;
use Oro\Bundle\FeatureToggleBundle\Event\FeaturesChange;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\FeatureToggleBundle\EventListener\ConfigListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConfigListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $featureConfigManager;

    /**
     * @var ConfigListener
     */
    protected $configListener;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);
        $this->featureConfigManager = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configListener = new ConfigListener($this->eventDispatcher, $this->featureConfigManager);
    }

    public function testOnUpdateAfter()
    {
        $feature1 = 'feature1';
        $feature2 = 'feature2';
        $feature3 = 'feature3';

        $configKey = 'oro_bundle.feature_toggle.key';
        $event = new ConfigUpdateEvent([$configKey => []]);

        $this->featureConfigManager->expects($this->once())
            ->method('getFeatureByToggle')
            ->with($configKey)
            ->willReturn($feature1);
        $this->featureConfigManager->expects($this->once())
            ->method('getFeatureDependents')
            ->with($feature1)
            ->willReturn([$feature2, $feature3]);

        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(FeaturesChange::NAME, new FeaturesChange());

        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(FeatureChange::NAME . '.' . $feature1, new FeatureChange());

        $this->eventDispatcher->expects($this->at(2))
            ->method('dispatch')
            ->with(FeatureChange::NAME . '.' . $feature2, new FeatureChange());

        $this->eventDispatcher->expects($this->at(3))
            ->method('dispatch')
            ->with(FeatureChange::NAME . '.' . $feature3, new FeatureChange());

        $this->configListener->onUpdateAfter($event);
    }
}
