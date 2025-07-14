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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConfigListenerTest extends TestCase
{
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private ConfigurationManager&MockObject $featureConfigManager;
    private FeatureChecker&MockObject $featureChecker;
    private ConfigListener $configListener;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->featureConfigManager = $this->createMock(ConfigurationManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->configListener = new ConfigListener(
            $this->eventDispatcher,
            $this->featureConfigManager,
            $this->featureChecker
        );
    }

    public function testOnSettingsSaveBefore(): void
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

        $configManager = $this->createMock(ConfigManager::class);
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

    public function testOnUpdateAfter(): void
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

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [new FeaturesChange(['feature2' => true]), FeaturesChange::NAME],
                [new FeatureChange('feature2', true), FeatureChange::NAME . '.feature2']
            );

        $this->configListener->onUpdateAfter();
    }

    public function testOnScopeIdChange(): void
    {
        $this->featureChecker->expects($this->once())
            ->method('resetCache');

        $this->configListener->onScopeIdChange();
    }
}
