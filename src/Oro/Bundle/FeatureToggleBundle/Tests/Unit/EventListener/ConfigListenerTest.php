<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;
use Oro\Bundle\FeatureToggleBundle\Event\FeatureChange;
use Oro\Bundle\FeatureToggleBundle\Event\FeaturesChange;
use Oro\Bundle\FeatureToggleBundle\EventListener\ConfigListener;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $eventDispatcher;

    /**
     * @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $featureConfigManager;

    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $featureChecker;

    /**
     * @var ConfigListener
     */
    protected $configListener;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->featureConfigManager = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configListener = new ConfigListener(
            $this->eventDispatcher,
            $this->featureConfigManager,
            $this->featureChecker
        );
    }

    public function testOnSettingsSaveBefore()
    {
        $feature1 = 'feature1';
        $feature2 = 'feature2';
        $feature3 = 'feature3';

        $configKey = 'oro_bundle.feature_toggle.key';

        $this->featureConfigManager->expects($this->once())
            ->method('getFeatureByToggle')
            ->with($configKey)
            ->willReturn($feature1);
        $this->featureConfigManager->expects($this->once())
            ->method('getFeatureDependents')
            ->with($feature1)
            ->willReturn([$feature2, $feature3]);

        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->featureConfigManager->expects($this->once())
            ->method('getFeatureByToggle')
            ->with($configKey)
            ->willReturn('feature1');

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['feature1', null, true],
                ['feature2', null, false],
                ['feature3', null, true],
            ]);

        $event = new ConfigSettingsUpdateEvent($configManager, [$configKey => []]);
        $this->configListener->onSettingsSaveBefore($event);

        $this->assertEquals(
            [
                'feature1' => true,
                'feature2' => false,
                'feature3' => true
            ],
            ReflectionUtil::getPropertyValue($this->configListener, 'featuresStates')
        );
        $this->assertEquals(
            ['feature1', 'feature2', 'feature3'],
            ReflectionUtil::getPropertyValue($this->configListener, 'affectedFeatures')
        );
    }

    public function testOnUpdateAfter()
    {
        ReflectionUtil::setPropertyValue(
            $this->configListener,
            'featuresStates',
            [
                'feature1' => true,
                'feature2' => false,
                'feature3' => true
            ]
        );
        ReflectionUtil::setPropertyValue(
            $this->configListener,
            'affectedFeatures',
            [
                'feature1',
                'feature2',
                'feature3'
            ]
        );

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturnMap([
               ['feature1', null, true],
               ['feature2', null, true],
               ['feature3', null, true],
            ]);

        $this->featureChecker->expects($this->once())
            ->method('resetCache');

        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(new FeaturesChange(['feature2' => true]), FeaturesChange::NAME);
        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(new FeatureChange('feature2', true), FeatureChange::NAME . '.feature2');

        $this->configListener->onUpdateAfter();
    }

    public function testOnScopeIdChange()
    {
        $this->featureChecker->expects($this->once())
            ->method('resetCache');

        $this->configListener->onScopeIdChange();
    }
}
