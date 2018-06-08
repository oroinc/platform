<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\ORM;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OroEntityManagerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient();
    }

    public function testCreateQueryWithDefaultQueryCacheLifeTime()
    {
        $this->assertNull($this->getContainer()->getParameter('oro_entity.default_query_cache_lifetime'));

        /** @var OroEntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->assertInstanceOf(OroEntityManager::class, $em);

        $cache = $this->createMock(Cache::class);
        $em->getConfiguration()->setQueryCacheImpl($cache);

        $query = $em->createQuery('SELECT 1 FROM Oro\Bundle\UserBundle\Entity\User u');
        $this->assertNull($query->getQueryCacheLifetime());

        $cache->expects($this->atLeastOnce())->method('save')->with($this->anything(), $this->anything(), null);
        $query->execute();

        $em->setDefaultQueryCacheLifetime(3600);
        $cache = $this->createMock(Cache::class);
        $em->getConfiguration()->setQueryCacheImpl($cache);

        $query = $em->createQuery('SELECT 1 FROM Oro\Bundle\UserBundle\Entity\User u');
        $this->assertEquals(3600, $query->getQueryCacheLifetime());

        $cache->expects($this->atLeastOnce())->method('save')->with($this->anything(), $this->anything(), 3600);
        $query->execute();
    }
}
