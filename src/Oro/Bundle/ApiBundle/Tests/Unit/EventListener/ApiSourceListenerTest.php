<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\EventListener;

use Oro\Bundle\ApiBundle\EventListener\ApiSourceListener;
use Oro\Bundle\ApiBundle\Provider\CacheManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\FeatureToggleBundle\Event\FeaturesChange;

class ApiSourceListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheManager|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheManager;

    /** @var ApiSourceListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->cacheManager = $this->createMock(CacheManager::class);

        $this->listener = new ApiSourceListener(
            $this->cacheManager,
            ['excluded_feature1', 'excluded_feature2']
        );
    }

    private function expectCachesCleared(): void
    {
        $this->cacheManager->expects(self::once())
            ->method('clearCaches');
        $this->cacheManager->expects(self::once())
            ->method('clearApiDocCache');
    }

    private function expectCachesNotCleared(): void
    {
        $this->cacheManager->expects(self::never())
            ->method('clearCaches');
        $this->cacheManager->expects(self::never())
            ->method('clearApiDocCache');
    }

    private function getPostFlushConfigEvent(array $models): PostFlushConfigEvent
    {
        return new PostFlushConfigEvent($models, $this->createMock(ConfigManager::class));
    }

    private function getEntityConfigModel(): EntityConfigModel
    {
        return new EntityConfigModel('Test\Entity');
    }

    private function getFieldConfigModel(array $extendOptions): FieldConfigModel
    {
        $model = new FieldConfigModel('testField');
        $model->fromArray('extend', $extendOptions);

        return $model;
    }

    public function testClearCache(): void
    {
        $this->expectCachesCleared();
        $this->listener->clearCache();
    }

    public function testOnFeaturesChangeForNotExcludedFeature(): void
    {
        $this->expectCachesCleared();
        $this->listener->onFeaturesChange(
            new FeaturesChange(['another_feature' => false])
        );
    }

    public function testOnFeaturesChangeForSeveralNotExcludedFeatures(): void
    {
        $this->expectCachesCleared();
        $this->listener->onFeaturesChange(
            new FeaturesChange(['another_feature1' => false, 'another_feature2' => false])
        );
    }

    public function testOnFeaturesChangeForExcludedFeature(): void
    {
        $this->expectCachesNotCleared();
        $this->listener->onFeaturesChange(
            new FeaturesChange(['excluded_feature2' => false])
        );
    }

    public function testOnFeaturesChangeForSeveralExcludedFeatures(): void
    {
        $this->expectCachesNotCleared();
        $this->listener->onFeaturesChange(
            new FeaturesChange(['excluded_feature1' => false, 'excluded_feature2' => false])
        );
    }

    public function testOnFeaturesChangeForBothExcludedAndNotExcludedFeatures(): void
    {
        $this->expectCachesCleared();
        $this->listener->onFeaturesChange(
            new FeaturesChange(['excluded_feature1' => false, 'another_feature1' => false])
        );
    }

    public function testOnEntityConfigPostFlushForEntitiesOnly(): void
    {
        $this->expectCachesNotCleared();
        $this->listener->onEntityConfigPostFlush(
            $this->getPostFlushConfigEvent([$this->getEntityConfigModel()])
        );
    }

    public function testOnEntityConfigPostFlushForNewRegularField(): void
    {
        $this->expectCachesNotCleared();
        $this->listener->onEntityConfigPostFlush(
            $this->getPostFlushConfigEvent([$this->getFieldConfigModel(['state' => ExtendScope::STATE_NEW])])
        );
    }

    public function testOnEntityConfigPostFlushForUpdatedRegularField(): void
    {
        $this->expectCachesCleared();
        $this->listener->onEntityConfigPostFlush(
            $this->getPostFlushConfigEvent([$this->getFieldConfigModel(['state' => ExtendScope::STATE_ACTIVE])])
        );
    }

    public function testOnEntityConfigPostFlushForUpdatedSerializedField(): void
    {
        $this->expectCachesCleared();
        $this->listener->onEntityConfigPostFlush(
            $this->getPostFlushConfigEvent([$this->getFieldConfigModel(['is_serialized' => true])])
        );
    }
}
