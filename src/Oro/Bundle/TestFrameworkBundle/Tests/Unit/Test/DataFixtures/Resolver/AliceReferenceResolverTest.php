<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Test\DataFixtures\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Resolver\AliceReferenceResolver;

class AliceReferenceResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var AliceReferenceResolver */
    private $aliceReferenceResolver;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->aliceReferenceResolver = new AliceReferenceResolver($this->registry);
    }

    public function testResolveWhenObjectsDoesNotContainReference(): void
    {
        $this->aliceReferenceResolver->setReferences(new Collection());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Reference "object" not found');

        $this->aliceReferenceResolver->resolve('@object');
    }

    /**
     * @dataProvider processProvider
     */
    public function testResolve(
        string $referencePath,
        object $object,
        bool $isContains,
        ?object $objectFromDb,
        $expectedValue
    ) {
        $this->aliceReferenceResolver->setReferences(new Collection(['ref' => $object]));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry->expects($this->once())
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

        $value = $this->aliceReferenceResolver->resolve($referencePath);
        $this->assertEquals($expectedValue, $value);
    }

    public function processProvider(): array
    {
        $refClassMock = new \stdClass();
        $proxyRefClassMock = $this->createMock(Proxy::class);
        $ownerId = 777;
        $ownerClassMock = $this->getMockBuilder(\stdClass::class)
            ->disableOriginalConstructor()
            ->addMethods(['getId'])
            ->getMock();
        $ownerClassMock->expects($this->any())
            ->method('getId')
            ->willReturn($ownerId);
        $refClassMock2 = $this->getMockBuilder(\stdClass::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOwner'])
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
        ];
    }
}
