<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Cache;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\EntityExtendBundle\EventListener\Cache\EnumValueListener;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Oro\Component\Testing\Unit\EntityTrait;

class EnumValueListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var EnumTranslationCache|\PHPUnit_Framework_MockObject_MockObject */
    private $cache;

    /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject */
    private $args;

    /** @var EnumValueListener */
    private $listener;

    public function setUp()
    {
        $this->cache = $this->createMock(EnumTranslationCache::class);
        $this->args = $this->createMock(LifecycleEventArgs::class);

        $this->listener = new EnumValueListener($this->cache);
    }

    public function testPostPersist()
    {
        $entity = new StubEnumValue(1, 'test');

        $this->cache->expects($this->once())
            ->method('invalidate')
            ->with(StubEnumValue::class);

        $this->listener->postPersist($entity, $this->args);
    }

    public function testPostPersistNotSupportedClass()
    {
        $entity = new \stdClass();

        $this->cache->expects($this->never())
            ->method('invalidate');

        $this->listener->postPersist($entity, $this->args);
    }

    public function testPostUpdate()
    {
        $entity = new StubEnumValue(1, 'test');

        $this->cache->expects($this->once())
            ->method('invalidate')
            ->with(StubEnumValue::class);

        $this->listener->postUpdate($entity, $this->args);
    }

    public function testPostUpdateNotSupportedClass()
    {
        $entity = new \stdClass();

        $this->cache->expects($this->never())
            ->method('invalidate');

        $this->listener->postUpdate($entity, $this->args);
    }

    public function testPostRemove()
    {
        $entity = new StubEnumValue(1, 'test');

        $this->cache->expects($this->once())
            ->method('invalidate')
            ->with(StubEnumValue::class);

        $this->listener->postRemove($entity, $this->args);
    }

    public function testPostRemoveNotSupportedClass()
    {
        $entity = new \stdClass();

        $this->cache->expects($this->never())
            ->method('invalidate');

        $this->listener->postRemove($entity, $this->args);
    }
}
