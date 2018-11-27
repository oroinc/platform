<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Query;
use Oro\Bundle\EntityBundle\ORM\OrmConfiguration;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RegistryTest extends \PHPUnit\Framework\TestCase
{
    const TEST_NAMESPACE_ALIAS    = 'Test';
    const TEST_NAMESPACE          = 'Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures';
    const TEST_ENTITY_CLASS       = TestEntity::class;
    const TEST_ENTITY_PROXY_CLASS = Proxy::class;

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var Registry */
    private $registry;

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

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|OroEntityManager
     */
    private function getManagerMock()
    {
        $managerConfiguration = new OrmConfiguration();
        $managerConfiguration->addEntityNamespace(self::TEST_NAMESPACE_ALIAS, self::TEST_NAMESPACE);

        $managerMetadataFactory = $this->createMock(ClassMetadataFactory::class);
        $managerMetadataFactory->expects(self::any())
            ->method('isTransient')
            ->willReturn(false);

        $manager = $this->getMockBuilder(OroEntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConfiguration', 'getMetadataFactory'])
            ->getMock();
        $manager->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($managerConfiguration);
        $manager->expects(self::any())
            ->method('getMetadataFactory')
            ->willReturn($managerMetadataFactory);

        return $manager;
    }

    public function testManagerServiceCache()
    {
        $manager1 = $this->getManagerMock();
        $manager2 = $this->getManagerMock();

        $this->container->expects(self::exactly(3))
            ->method('get')
            ->with('service.default')
            ->will(self::onConsecutiveCalls($manager1, $manager1, $manager2));
        $this->container->expects(self::once())
            ->method('initialized')
            ->willReturnMap([['service.default', true]]);

        self::assertSame($manager1, $this->registry->getManager('default'));
        // test that a manager service cached
        self::assertSame($manager1, $this->registry->getManager('default'));

        self::assertSame($manager2, $this->registry->resetManager('default'));

        self::assertSame($manager2, $this->registry->getManager('default'));
        // test that a manager cached
        self::assertSame($manager2, $this->registry->getManager('default'));
    }

    public function testManagerCache()
    {
        $manager1 = $this->getManagerMock();
        $manager2 = $this->getManagerMock();

        $this->container->expects(self::exactly(3))
            ->method('get')
            ->with('service.default')
            ->will(self::onConsecutiveCalls($manager1, $manager1, $manager2));
        $this->container->expects(self::once())
            ->method('initialized')
            ->willReturnMap([['service.default', true]]);

        self::assertSame($manager1, $this->registry->getManagerForClass(self::TEST_ENTITY_CLASS));
        // test that a manager cached
        self::assertSame($manager1, $this->registry->getManagerForClass(self::TEST_ENTITY_CLASS));

        self::assertSame($manager2, $this->registry->resetManager());

        self::assertSame($manager2, $this->registry->getManagerForClass(self::TEST_ENTITY_CLASS));
        // test that a manager cached
        self::assertSame($manager2, $this->registry->getManagerForClass(self::TEST_ENTITY_CLASS));
    }

    public function testManagerCacheWhenEntityManagerDoesNotExist()
    {
        $this->container->expects(self::once())
            ->method('get')
            ->with('service.default')
            ->willReturn(null);
        $this->container->expects(self::once())
            ->method('initialized')
            ->willReturnMap([['service.default', false]]);

        self::assertNull($this->registry->getManagerForClass(self::TEST_ENTITY_PROXY_CLASS));
        // test that a manager cached
        self::assertNull($this->registry->getManagerForClass(self::TEST_ENTITY_PROXY_CLASS));

        self::assertNull($this->registry->resetManager());

        self::assertNull($this->registry->getManagerForClass(self::TEST_ENTITY_PROXY_CLASS));
        // test that a manager cached
        self::assertNull($this->registry->getManagerForClass(self::TEST_ENTITY_PROXY_CLASS));
    }

    public function testDefaultQueryCacheLifetimeWhenItWasSpecifiedExplicitly()
    {
        $defaultQueryCacheLifetime = 3600;

        $manager = $this->getManagerMock();

        $this->container->expects(self::atLeastOnce())
            ->method('get')
            ->with('service.default')
            ->willReturn($manager);

        $this->registry->setDefaultQueryCacheLifetime($defaultQueryCacheLifetime);

        /** @var Query $query */
        $query = $this->registry->getManager('default')
            ->createQuery('SELECT * FROM ' . self::TEST_ENTITY_CLASS);
        self::assertSame($defaultQueryCacheLifetime, $query->getQueryCacheLifetime());
    }

    public function testDefaultQueryCacheLifetimeWhenItWasNotSpecified()
    {
        $manager = $this->getManagerMock();

        $this->container->expects(self::atLeastOnce())
            ->method('get')
            ->with('service.default')
            ->willReturn($manager);

        /** @var Query $query */
        $query = $this->registry->getManager('default')
            ->createQuery('SELECT * FROM ' . self::TEST_ENTITY_CLASS);
        self::assertNull($query->getQueryCacheLifetime());
    }

    public function testDefaultQueryCacheLifetimeWhenItWasSetToZero()
    {
        $manager = $this->getManagerMock();

        $this->container->expects(self::atLeastOnce())
            ->method('get')
            ->with('service.default')
            ->willReturn($manager);

        $this->registry->setDefaultQueryCacheLifetime(0);

        /** @var Query $query */
        $query = $this->registry->getManager('default')
            ->createQuery('SELECT * FROM ' . self::TEST_ENTITY_CLASS);
        self::assertSame(0, $query->getQueryCacheLifetime());
    }

    public function testGetAliasNamespaceForKnownAlias()
    {
        $manager1 = $this->getManagerMock();

        $this->container->expects(self::once())
            ->method('get')
            ->with('service.default')
            ->willReturn($manager1);

        self::assertEquals(
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

        $this->container->expects(self::once())
            ->method('get')
            ->with('service.default')
            ->willReturn($manager1);

        $this->registry->getAliasNamespace('Another');
    }
}
