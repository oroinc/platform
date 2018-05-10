<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Cache;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\EntityExtendBundle\Entity\EnumValueTranslation;
use Oro\Bundle\EntityExtendBundle\EventListener\Cache\EnumValueTranslationListener;
use Oro\Component\Testing\Unit\EntityTrait;

class EnumValueTranslationListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var EnumTranslationCache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cache;

    /**
     * @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject
     */
    private $args;

    /**
     * @var EnumValueTranslationListener
     */
    private $listener;

    public function setUp()
    {
        $this->cache = $this->createMock(EnumTranslationCache::class);
        $this->args = $this->createMock(LifecycleEventArgs::class);
        $this->listener = new EnumValueTranslationListener($this->cache);
    }

    public function testPostPersist()
    {
        /** @var EnumValueTranslation $entity */
        $entity = $this->getEntity(EnumValueTranslation::class, ['id' => 1, 'objectClass' => 'className']);

        $this->cache->expects($this->once())
            ->method('invalidate')
            ->with('className');

        $this->listener->postPersist($entity, $this->args);
    }

    public function testPostUpdate()
    {
        /** @var EnumValueTranslation $entity */
        $entity = $this->getEntity(EnumValueTranslation::class, ['id' => 1, 'objectClass' => 'className']);

        $this->cache->expects($this->once())
            ->method('invalidate')
            ->with('className');

        $this->listener->postUpdate($entity, $this->args);
    }

    public function testPostRemove()
    {
        /** @var EnumValueTranslation $entity */
        $entity = $this->getEntity(EnumValueTranslation::class, ['id' => 1, 'objectClass' => 'className']);

        $this->cache->expects($this->once())
            ->method('invalidate')
            ->with('className');

        $this->listener->postRemove($entity, $this->args);
    }
}
