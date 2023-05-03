<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Cache\CacheInterface;

class GlobalScopeManagerTest extends AbstractScopeManagerTestCase
{
    /** @var GlobalScopeManager */
    protected $manager;

    /**
     * {@inheritdoc}
     */
    protected function createManager(
        ManagerRegistry $doctrine,
        CacheInterface $cache,
        EventDispatcher $eventDispatcher,
        ConfigBag $configBag,
    ): GlobalScopeManager {
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
