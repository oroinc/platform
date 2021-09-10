<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class EntityClassResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EntityClassResolver */
    private $resolver;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->resolver = new EntityClassResolver($this->doctrine);
    }

    public function testGetEntityClassWithFullClassName()
    {
        $testClass = 'Acme\Bundle\SomeBundle\SomeClass';
        $this->assertEquals($testClass, $this->resolver->getEntityClass($testClass));
    }

    public function testGetEntityClassWithInvalidEntityName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->resolver->getEntityClass('AcmeSomeBundle:Entity:SomeClass');
    }

    public function testGetEntityClassWithUnknownEntityName()
    {
        $this->expectException(ORMException::class);
        $this->doctrine->expects($this->once())
            ->method('getAliasNamespace')
            ->with('AcmeSomeBundle')
            ->willThrowException(new ORMException());
        $this->resolver->getEntityClass('AcmeSomeBundle:SomeClass');
    }

    public function testGetEntityClass()
    {
        $this->doctrine->expects($this->once())
            ->method('getAliasNamespace')
            ->with('AcmeSomeBundle')
            ->willReturn('Acme\Bundle\SomeBundle');
        $this->assertEquals(
            'Acme\Bundle\SomeBundle\SomeClass',
            $this->resolver->getEntityClass('AcmeSomeBundle:SomeClass')
        );
    }

    public function testIsKnownEntityClassNamespace()
    {
        $config = $this->createMock(Configuration::class);
        $config->expects($this->exactly(2))
            ->method('getEntityNamespaces')
            ->willReturn(['AcmeSomeBundle' => 'Acme\Bundle\SomeBundle\Entity']);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->exactly(2))
            ->method('getConfiguration')
            ->willReturn($config);

        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerNames')
            ->willReturn(['default' => 'service.default']);
        $this->doctrine->expects($this->exactly(2))
            ->method('getManager')
            ->with('default')
            ->willReturn($em);

        $this->assertTrue($this->resolver->isKnownEntityClassNamespace('Acme\Bundle\SomeBundle\Entity'));
        $this->assertFalse($this->resolver->isKnownEntityClassNamespace('Acme\Bundle\AnotherBundle\Entity'));
    }

    public function testIsEntity()
    {
        $className = 'Test\Entity';

        $em = $this->createMock(EntityManager::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($em);

        $this->assertTrue(
            $this->resolver->isEntity($className)
        );
    }

    public function testIsEntityForNotManageableEntity()
    {
        $className = 'Test\Entity';

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn(null);

        $this->assertFalse(
            $this->resolver->isEntity($className)
        );
    }
}
