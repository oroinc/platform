<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\EventListener;

use Oro\Bundle\ApiBundle\EventListener\ApiSourceListener;
use Oro\Bundle\ApiBundle\Provider\CacheManager;
use Oro\Bundle\FeatureToggleBundle\Event\FeaturesChange;

class ApiSourceListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheManager|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheManager;

    /** @var ApiSourceListener */
    private $listener;

    protected function setUp(): void
    {
        $this->cacheManager = $this->createMock(CacheManager::class);

        $this->listener = new ApiSourceListener(
            $this->cacheManager,
            ['excluded_feature1', 'excluded_feature2']
        );
    }

    private function expectCachesCleared()
    {
        $this->cacheManager->expects(self::once())
            ->method('clearCaches');
        $this->cacheManager->expects(self::once())
            ->method('clearApiDocCache');
    }

    private function expectCachesNotCleared()
    {
        $this->cacheManager->expects(self::never())
            ->method('clearCaches');
        $this->cacheManager->expects(self::never())
            ->method('clearApiDocCache');
    }

    public function testClearCache()
    {
        $this->expectCachesCleared();
        $this->listener->clearCache();
    }

    public function testOnFeaturesChangeForNotExcludedFeature()
    {
        $this->expectCachesCleared();
        $this->listener->onFeaturesChange(
            new FeaturesChange(['another_feature' => false])
        );
    }

    public function testOnFeaturesChangeForSeveralNotExcludedFeatures()
    {
        $this->expectCachesCleared();
        $this->listener->onFeaturesChange(
            new FeaturesChange(['another_feature1' => false, 'another_feature2' => false])
        );
    }

    public function testOnFeaturesChangeForExcludedFeature()
    {
        $this->expectCachesNotCleared();
        $this->listener->onFeaturesChange(
            new FeaturesChange(['excluded_feature2' => false])
        );
    }

    public function testOnFeaturesChangeForSeveralExcludedFeatures()
    {
        $this->expectCachesNotCleared();
        $this->listener->onFeaturesChange(
            new FeaturesChange(['excluded_feature1' => false, 'excluded_feature2' => false])
        );
    }

    public function testOnFeaturesChangeForBothExcludedAndNotExcludedFeatures()
    {
        $this->expectCachesCleared();
        $this->listener->onFeaturesChange(
            new FeaturesChange(['excluded_feature1' => false, 'another_feature1' => false])
        );
    }
}
