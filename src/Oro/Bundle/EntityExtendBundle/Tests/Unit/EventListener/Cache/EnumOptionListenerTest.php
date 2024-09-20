<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Cache;

use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionTranslation;
use Oro\Bundle\EntityExtendBundle\EventListener\Cache\EnumOptionListener;
use Oro\Bundle\EntityExtendBundle\Test\EntityExtendTestInitializer;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Component\Testing\Unit\EntityTrait;

class EnumOptionListenerTest extends EnumValueListenerTestCase
{
    use EntityTrait;

    private EnumOptionListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new EnumOptionListener(
            $this->doctrine,
            $this->cache,
            $this->translationManager,
            $this->translationHelper,
            $this->messageProducer
        );
        EntityExtendTestInitializer::initialize();
    }

    public function testPostPersist()
    {
        $this->assertClearCacheCalled();

        $this->listener->postPersist($this->getEntityInstance());
    }

    public function testPostPersistNotSupportedClass()
    {
        $this->assertClearCacheNotCalled();

        $this->listener->postPersist(new \stdClass());
    }

    public function testPostUpdate()
    {
        $this->assertClearCacheCalled();

        $this->listener->postUpdate($this->getEntityInstance());
    }

    public function testPostUpdateNotSupportedClass()
    {
        $this->assertClearCacheNotCalled();

        $this->listener->postUpdate(new \stdClass());
    }

    public function testPostRemove()
    {
        $this->assertClearCacheCalled();

        $this->listener->postRemove($this->getEntityInstance());
    }

    public function testPostFlush()
    {
        $this->assetTranslationRemoved();
        $this->assertClearCacheCalled();

        $this->listener->postRemove($this->getEntityInstance());
        $this->listener->postFlush();
    }

    public function testPostRemoveNotSupportedClass()
    {
        $this->assertClearCacheNotCalled();

        $this->listener->postRemove(new \stdClass());
    }

    /**
     * @return EnumOptionTranslation|object
     */
    private function getEntityInstance()
    {
        return $this->getEntity(
            TestEnumValue::class,
            ['enumCode' => self::ENUM_CODE, 'name' => 'Test', 'internalId' => 'test', 'priority' => 1],
            ['enumCode', 'name', 'internalId', 1]
        );
    }
}
