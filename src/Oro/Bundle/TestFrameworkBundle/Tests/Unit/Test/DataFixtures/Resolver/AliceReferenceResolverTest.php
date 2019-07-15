<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Test\DataFixtures\Resolver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Resolver\AliceReferenceResolver;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AliceReferenceResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RegistryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var AliceReferenceResolver
     */
    private $resolver;

    public function testResolveWhenObjectsDoesNotContainReference(): void
    {
        $value = '@object';
        $this->resolver->setReferences(new Collection());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Reference "object" not found');
        $this->resolver->resolve($value);
    }

    /**
     * @param string $referencePath
     * @param object $object
     * @param bool $isContains
     * @param object|null $objectFromDb
     * @param mixed $expectedValue
     * @dataProvider processProvider
     */
    public function testResolve($referencePath, $object, $isContains, $objectFromDb, $expectedValue): void
    {
        $this->resolver->setReferences(new Collection(['ref' => $object]));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(get_class($object))
            ->willReturn($entityManager);
        $entityManager->expects($this->any())
            ->method('contains')
            ->with($object)
            ->willReturn($isContains);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(get_class($object))
            ->willReturn($classMetadata);
        $identifier = 777;
        $classMetadata->expects($this->any())
            ->method('getIdentifierValues')
            ->with($object)
            ->willReturn($identifier);
        $entityManager->expects($this->any())
            ->method('find')
            ->with(get_class($object), $identifier)
            ->willReturn($objectFromDb);

        $value = $this->resolver->resolve($referencePath);
        $this->assertEquals($expectedValue, $value);
    }

    /**
     * @return array
     */
    public function processProvider()
    {
        $refClassMock = new \stdClass();
        $proxyRefClassMock = $this->createMock(Proxy::class);
        $ownerId = 777;
        $ownerClassMock = $this->getMockBuilder(\stdClass::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $ownerClassMock->expects($this->any())
            ->method('getId')
            ->willReturn($ownerId);
        $refClassMock2 = $this->getMockBuilder(\stdClass::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOwner'])
            ->getMock();
        $refClassMock2->expects($this->any())
            ->method('getOwner')
            ->willReturn($ownerClassMock);

        return [
            'simple, not proxy, contains' => [
                'referencePath' => '@ref',
                'object' => $refClassMock,
                'isContains' => true,
                'objectFromDb' => null,
                'expectedValue' => $refClassMock,
            ],
            'simple, proxy, contains' => [
                'referencePath' => '@ref',
                'object' => $proxyRefClassMock,
                'isContains' => true,
                'objectFromDb' => null,
                'expectedValue' => $proxyRefClassMock,
            ],
            'simple, proxy, not contains' => [
                'referencePath' => '@ref',
                'object' => $proxyRefClassMock,
                'isContains' => false,
                'objectFromDb' => $refClassMock,
                'expectedValue' => $refClassMock,
            ],
            'complex, not proxy, contains' => [
                'referencePath' => '@ref->owner->getId()',
                'object' => $refClassMock2,
                'isContains' => true,
                'objectFromDb' => null,
                'expectedValue' => $ownerId,
            ],
            'complex, proxy, not contains' => [
                'referencePath' => '@ref->owner->getId()',
                'object' => $proxyRefClassMock,
                'isContains' => false,
                'objectFromDb' => $refClassMock2,
                'expectedValue' => $ownerId,
            ],
            'cannot be resolved 1' => [
                'referencePath' => 'ref',
                'object' => $proxyRefClassMock,
                'isContains' => false,
                'objectFromDb' => $refClassMock2,
                'expectedValue' => 'ref',
            ],
            'cannot be resolved 2' => [
                'referencePath' => '@ref*',
                'object' => $proxyRefClassMock,
                'isContains' => false,
                'objectFromDb' => $refClassMock2,
                'expectedValue' => '@ref*',
            ],
            'cannot be resolved 3' => [
                'referencePath' => '@ref<current()>',
                'object' => $proxyRefClassMock,
                'isContains' => false,
                'objectFromDb' => $refClassMock2,
                'expectedValue' => '@ref<current()>',
            ],
            'cannot be resolved 4' => [
                'referencePath' => '<current()>',
                'object' => $proxyRefClassMock,
                'isContains' => false,
                'objectFromDb' => $refClassMock2,
                'expectedValue' => '<current()>',
            ],
            'cannot be resolved 5' => [
                'referencePath' => '<current()>@example.org',
                'object' => $proxyRefClassMock,
                'isContains' => false,
                'objectFromDb' => $refClassMock2,
                'expectedValue' => '<current()>@example.org',
            ],
        ];
    }

    protected function setUp()
    {
        $this->registry = $this->createMock(RegistryInterface::class);
        $this->resolver = new AliceReferenceResolver($this->registry);
    }
}
