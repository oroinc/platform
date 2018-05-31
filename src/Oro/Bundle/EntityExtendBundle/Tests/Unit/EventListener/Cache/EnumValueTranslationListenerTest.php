<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Cache;

use Oro\Bundle\EntityExtendBundle\Entity\EnumValueTranslation;
use Oro\Bundle\EntityExtendBundle\EventListener\Cache\EnumValueTranslationListener;
use Oro\Component\Testing\Unit\EntityTrait;

class EnumValueTranslationListenerTest extends EnumValueListenerTestCase
{
    use EntityTrait;

    /** @var EnumValueTranslationListener */
    private $listener;

    public function setUp()
    {
        parent::setUp();

        $this->listener = new EnumValueTranslationListener($this->cache);
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

    public function testPostRemoveNotSupportedClass()
    {
        $this->assertClearCacheNotCalled();

        $this->listener->postRemove(new \stdClass());
    }

    /**
     * @return EnumValueTranslation|object
     */
    private function getEntityInstance()
    {
        return $this->getEntity(EnumValueTranslation::class, ['id' => 42, 'objectClass' => self::ENUM_VALUE_CLASS]);
    }
}
