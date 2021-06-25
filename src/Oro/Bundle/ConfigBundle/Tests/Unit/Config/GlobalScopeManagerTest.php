<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Symfony\Component\EventDispatcher\EventDispatcher;

class GlobalScopeManagerTest extends AbstractScopeManagerTestCase
{
    /** @var GlobalScopeManager */
    protected $manager;

    /**
     * {@inheritdoc}
     *
     * @return GlobalScopeManager
     */
    protected function createManager(
        ManagerRegistry $doctrine,
        CacheProvider $cache,
        EventDispatcher $eventDispatcher,
        ConfigBag $configBag
    ) {
        return new GlobalScopeManager($doctrine, $cache, $eventDispatcher, $configBag);
    }

    /**
     * {@inheritdoc}
     */
    protected function getScopedEntityName(): string
    {
        return 'app';
    }

    public function testGetScopeIdFromEntity(): void
    {
        $entity = $this->getScopedEntity();
        $this->assertSame(0, $this->manager->getScopeIdFromEntity($entity));
    }

    public function testGetScopeIdFromUnsupportedEntity(): void
    {
        $entity = new \stdClass();
        $this->assertSame(0, $this->manager->getScopeIdFromEntity($entity));
    }
}
