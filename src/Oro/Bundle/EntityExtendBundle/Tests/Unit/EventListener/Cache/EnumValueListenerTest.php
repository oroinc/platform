<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Cache;

use Oro\Bundle\EntityExtendBundle\EventListener\Cache\EnumValueListener;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

class EnumValueListenerTest extends EnumValueListenerTestCase
{
    /** @var EnumValueListener */
    private $listener;

    public function setUp()
    {
        parent::setUp();

        $this->listener = new EnumValueListener($this->cache);
    }

    public function testPostPersist()
    {
        $this->assertClearCacheCalled();

        $this->listener->postPersist(new StubEnumValue(1, 'test'));
    }

    public function testPostPersistNotSupportedClass()
    {
        $this->assertClearCacheNotCalled();

        $this->listener->postPersist(new \stdClass());
    }

    public function testPostUpdate()
    {
        $this->assertClearCacheCalled();

        $this->listener->postUpdate(new StubEnumValue(1, 'test'));
    }

    public function testPostUpdateNotSupportedClass()
    {
        $this->assertClearCacheNotCalled();

        $this->listener->postUpdate(new \stdClass());
    }

    public function testPostRemove()
    {
        $this->assertClearCacheCalled();

        $this->listener->postRemove(new StubEnumValue(1, 'test'));
    }

    public function testPostRemoveNotSupportedClass()
    {
        $this->assertClearCacheNotCalled();

        $this->listener->postRemove(new \stdClass());
    }
}
