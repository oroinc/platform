<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigRepository;
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

    public function testReload()
    {
        $repo = $this->createMock(ConfigRepository::class);
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())->method('getRepository')->with(Config::class)->willReturn($repo);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Config::class)
            ->willReturn($em);

        $cache = $this->createMock(CacheProvider::class);
        $dispatcher = $this->createMock(EventDispatcher::class);
        $configBag = $this->createMock(ConfigBag::class);

        $manager = $this->createManager($doctrine, $cache, $dispatcher, $configBag);
        $cache->expects($this->once())
            ->method('deleteAll');
        $manager->reload();
    }
}
