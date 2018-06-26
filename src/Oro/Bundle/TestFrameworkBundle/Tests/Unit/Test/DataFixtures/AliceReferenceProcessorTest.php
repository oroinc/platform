<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Test\DataFixtures;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use Nelmio\Alice\Instances\Collection;
use Nelmio\Alice\Instances\Processor\Processable;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AliceReferenceProcessor;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AliceReferenceProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RegistryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var AliceReferenceProcessor
     */
    private $aliceReferenceProcessor;

    protected function setUp()
    {
        $this->registry = $this->createMock(RegistryInterface::class);
        $this->aliceReferenceProcessor = new AliceReferenceProcessor($this->registry);
    }

    /**
     * @dataProvider getProcessedValues
     * @param bool   $expectedMatch
     * @param string $value
     */
    public function testCanProcess($expectedMatch, $value)
    {
        $processable = new Processable($value);

        self::assertSame($expectedMatch, $this->aliceReferenceProcessor->canProcess($processable));
    }

    /**
     * @return array
     */
    public function getProcessedValues()
    {
        return [
            [true,  '@ref'],
            [true,  '@ref->id'],
            [true,  '@ref->owner->getId()'],
            [false, 'ref'],
            [false, '@ref*'],
            [false, '@ref<current()>'],
            [false, '<current()>'],
            [false, '<current()>@example.org'],
        ];
    }

    public function testProcessWhenObjectsDoesNotContainReference()
    {
        $processable = new Processable('@object');
        $this->aliceReferenceProcessor->setObjects(new Collection());
        $this->aliceReferenceProcessor->canProcess($processable);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Reference "object" not found');
        $this->aliceReferenceProcessor->process($processable, []);
    }

    /**
     * @param string $referencePath
     * @param object $object
     * @param bool $isContains
     * @param object|null $objectFromDb
     * @param mixed $expectedValue
     * @dataProvider processProvider
     */
    public function testProcess($referencePath, $object, $isContains, $objectFromDb, $expectedValue)
    {
        $processable = new Processable($referencePath);
        $this->aliceReferenceProcessor->setObjects(new Collection(['ref' => $object]));
        $this->aliceReferenceProcessor->canProcess($processable);

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

        $value = $this->aliceReferenceProcessor->process($processable, []);
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
        ];
    }
}
