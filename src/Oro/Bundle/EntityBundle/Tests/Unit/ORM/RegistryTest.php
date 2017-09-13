<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityBundle\ORM\Registry;

class RegistryTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS       = 'Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity';
    const TEST_ENTITY_PROXY_CLASS = 'Doctrine\ORM\Proxy\Proxy';

    /** @var \PHPUnit_Framework_MockObject_MockObject|Registry */
    protected $registry;

    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    protected function setUp()
    {
        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->registry = new Registry(
            $this->container,
            [''],
            ['default' => 'service.default'],
            '',
            'default'
        );
    }

    public function testManagerCache()
    {
        $manager1 = $this->getManagerMock();
        $manager2 = $this->getManagerMock();

        $manager1->expects($this->atLeastOnce())
            ->method('setDefaultQueryCacheLifetime')
            ->with(3600);
        $manager2->expects($this->atLeastOnce())
            ->method('setDefaultQueryCacheLifetime')
            ->with(3600);

        $this->container->expects($this->atLeastOnce())
            ->method('get')
            ->with('service.default')
            ->will($this->onConsecutiveCalls($manager1, $manager1, $manager2, $manager2));
        $this->container->expects($this->atLeastOnce())
            ->method('getParameter')
            ->with('oro_entity.default_query_cache_lifetime')
            ->willReturn(3600);
        $this->container->expects($this->atLeastOnce())
            ->method('set')
            ->with('service.default', null);

        $this->assertSame($manager1, $this->registry->getManagerForClass(self::TEST_ENTITY_CLASS));
        // test that a manager cached
        $this->assertSame($manager1, $this->registry->getManagerForClass(self::TEST_ENTITY_CLASS));

        $this->registry->resetManager();

        $this->assertSame($manager2, $this->registry->getManagerForClass(self::TEST_ENTITY_CLASS));
        // test that a manager cached
        $this->assertSame($manager2, $this->registry->getManagerForClass(self::TEST_ENTITY_CLASS));
    }

    public function testManagerCacheWhenEntityManagerDoesNotExist()
    {
        $this->assertNull($this->registry->getManagerForClass(self::TEST_ENTITY_PROXY_CLASS));
        // test that a manager cached
        $this->assertNull($this->registry->getManagerForClass(self::TEST_ENTITY_PROXY_CLASS));

        $this->registry->resetManager();

        $this->assertNull($this->registry->getManagerForClass(self::TEST_ENTITY_PROXY_CLASS));
        // test that a manager cached
        $this->assertNull($this->registry->getManagerForClass(self::TEST_ENTITY_PROXY_CLASS));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getManagerMock()
    {
        $managerMetadataFactory = $this->createMock('Doctrine\Common\Persistence\Mapping\ClassMetadataFactory');
        $managerMetadataFactory->expects($this->any())
            ->method('isTransient')
            ->willReturn(false);
        $manager = $this->createMock(OroEntityManager::class);
        $manager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($managerMetadataFactory);

        return $manager;
    }
}
