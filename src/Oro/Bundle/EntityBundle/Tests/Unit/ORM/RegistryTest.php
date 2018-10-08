<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\LazyOroEntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RegistryTest extends \PHPUnit\Framework\TestCase
{
    const TEST_NAMESPACE_ALIAS    = 'Test';
    const TEST_NAMESPACE          = 'Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures';
    const TEST_ENTITY_CLASS       = 'Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity';
    const TEST_ENTITY_PROXY_CLASS = 'Doctrine\ORM\Proxy\Proxy';

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var Registry */
    protected $registry;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->registry = new Registry(
            $this->container,
            [''],
            ['default' => 'service.default'],
            '',
            'default'
        );
    }

    public function testManagerServiceCache()
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
            ->will($this->onConsecutiveCalls($manager1, $manager1, $manager2));
        $this->container->expects($this->atLeastOnce())
            ->method('getParameter')
            ->with('oro_entity.default_query_cache_lifetime')
            ->willReturn(3600);

        $manager1->expects($this->atLeastOnce())
            ->method('setProxyInitializer')
            ->with($this->isInstanceOf(\Closure::class));
        $this->container
            ->expects($this->any())
            ->method('initialized')
            ->willReturnMap([['service.default', true]]);

        $this->assertSame($manager1, $this->registry->getManager('default'));
        // test that a manager service cached
        $this->assertSame($manager1, $this->registry->getManager('default'));

        $this->assertSame($manager2, $this->registry->resetManager('default'));

        $this->assertSame($manager2, $this->registry->getManager('default'));
        // test that a manager cached
        $this->assertSame($manager2, $this->registry->getManager('default'));
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
            ->will($this->onConsecutiveCalls($manager1, $manager1, $manager2));
        $this->container->expects($this->atLeastOnce())
            ->method('getParameter')
            ->with('oro_entity.default_query_cache_lifetime')
            ->willReturn(3600);
        $manager1->expects($this->atLeastOnce())
            ->method('setProxyInitializer')
            ->with($this->isInstanceOf(\Closure::class));

        $this->container
            ->expects($this->any())
            ->method('initialized')
            ->willReturnMap([['service.default', true]]);

        $this->assertSame($manager1, $this->registry->getManagerForClass(self::TEST_ENTITY_CLASS));
        // test that a manager cached
        $this->assertSame($manager1, $this->registry->getManagerForClass(self::TEST_ENTITY_CLASS));

        $this->assertSame($manager2, $this->registry->resetManager());

        $this->assertSame($manager2, $this->registry->getManagerForClass(self::TEST_ENTITY_CLASS));
        // test that a manager cached
        $this->assertSame($manager2, $this->registry->getManagerForClass(self::TEST_ENTITY_CLASS));
    }

    public function testManagerCacheWhenEntityManagerDoesNotExist()
    {
        $this->container->expects($this->exactly(1))
            ->method('get')
            ->with('service.default')
            ->willReturn(null);

        $this->container
            ->expects($this->any())
            ->method('initialized')
            ->willReturnMap([['service.default', false]]);

        $this->assertNull($this->registry->getManagerForClass(self::TEST_ENTITY_PROXY_CLASS));
        // test that a manager cached
        $this->assertNull($this->registry->getManagerForClass(self::TEST_ENTITY_PROXY_CLASS));

        $this->assertNull($this->registry->resetManager());

        $this->assertNull($this->registry->getManagerForClass(self::TEST_ENTITY_PROXY_CLASS));
        // test that a manager cached
        $this->assertNull($this->registry->getManagerForClass(self::TEST_ENTITY_PROXY_CLASS));
    }

    public function testGetAliasNamespaceForKnownAlias()
    {
        $manager1 = $this->getManagerMock();

        $this->container->expects($this->once())
            ->method('get')
            ->with('service.default')
            ->willReturn($manager1);

        $this->assertEquals(
            self::TEST_NAMESPACE,
            $this->registry->getAliasNamespace(self::TEST_NAMESPACE_ALIAS)
        );
    }

    /**
     * @expectedException \Doctrine\ORM\ORMException
     */
    public function testGetAliasNamespaceForUnknownAlias()
    {
        $manager1 = $this->getManagerMock();

        $this->container->expects($this->once())
            ->method('get')
            ->with('service.default')
            ->willReturn($manager1);

        $this->registry->getAliasNamespace('Another');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getManagerMock()
    {
        $managerConfiguration = $this->getMockBuilder('Doctrine\ORM\Configuration')
            ->disableOriginalConstructor()
            ->getMock();
        $managerConfiguration->expects($this->any())
            ->method('getEntityNamespaces')
            ->willReturn([self::TEST_NAMESPACE_ALIAS => self::TEST_NAMESPACE]);

        $managerMetadataFactory = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $managerMetadataFactory->expects($this->any())
            ->method('isTransient')
            ->willReturn(false);

        $manager = $this->createMock(LazyOroEntityManager::class);
        $manager->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($managerConfiguration);
        $manager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($managerMetadataFactory);

        return $manager;
    }
}
