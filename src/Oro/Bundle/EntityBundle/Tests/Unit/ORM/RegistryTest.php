<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Oro\Bundle\EntityBundle\ORM\Registry;

class RegistryTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS       = 'Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity';
    const TEST_ENTITY_PROXY_CLASS = 'Doctrine\ORM\Proxy\Proxy';

    /** @var \PHPUnit_Framework_MockObject_MockObject|Registry */
    protected $registry;

    protected function setUp()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->registry = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\Registry')
            ->setConstructorArgs([$container, [''], ['default' => 'default'], '', 'default'])
            ->setMethods(['getService', 'resetService'])
            ->getMock();
    }

    public function testManagerCache()
    {
        $manager1 = $this->getManagerMock();
        $manager2 = $this->getManagerMock();

        $this->registry->expects($this->at(0))
            ->method('getService')
            ->willReturn($manager1);
        $this->registry->expects($this->at(1))
            ->method('resetService');
        $this->registry->expects($this->at(2))
            ->method('getService')
            ->willReturn($manager2);

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
        $this->registry->expects($this->once())
            ->method('resetService');

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
        $managerMetadataFactory = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadataFactory');
        $managerMetadataFactory->expects($this->any())
            ->method('isTransient')
            ->willReturn(false);
        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $manager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($managerMetadataFactory);

        return $manager;
    }
}
