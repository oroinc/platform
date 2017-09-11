<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\ORM;

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
        /** @var OroEntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->assertInstanceOf(OroEntityManager::class, $em);

        $em->setDefaultQueryCacheLifetime(3600);
        $this->assertEquals(3600, $em->createQuery('SELECT 1')->getQueryCacheLifetime());
        $em->setDefaultQueryCacheLifetime(null);
        $this->assertNull($em->createQuery('SELECT 1')->getQueryCacheLifetime());
    }
}
