<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProvider;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity;

class EntityNameProviderTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadata;

    /** @var EntityNameProvider */
    protected $entityNameProvider;

    protected function setUp()
    {
        $this->doctrine = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $manager        = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadata = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap(
                [
                    [self::ENTITY_CLASS, $manager]
                ]
            );
        $manager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturnMap(
                [
                    [self::ENTITY_CLASS, $this->metadata]
                ]
            );

        $this->entityNameProvider = new EntityNameProvider($this->doctrine);
    }

    public function testGetNameForUnsupportedFormat()
    {
        $result = $this->entityNameProvider->getName('test', null, new TestEntity());
        $this->assertFalse($result);
    }

    public function testGetName()
    {
        $entity = new TestEntity();
        $entity->setName('test');

        $this->metadata->expects($this->any())
            ->method('hasField')
            ->willReturnMap(
                [
                    ['name', true]
                ]
            );
        $this->metadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('name')
            ->willReturn('string');

        $result = $this->entityNameProvider->getName('short', null, $entity);
        $this->assertEquals('test', $result);
    }

    public function testGetNameForNotManageableEntity()
    {
        $entity = new \stdClass();

        $result = $this->entityNameProvider->getName('short', null, $entity);
        $this->assertFalse($result);
    }

    public function testGetNameNoAppropriateField()
    {
        $entity = new TestEntity();

        $result = $this->entityNameProvider->getName('short', null, $entity);
        $this->assertFalse($result);
    }

    public function testGetNameShortEmptyNameButHasIdentifier()
    {
        $entity = new TestEntity(123);

        $this->metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->metadata->expects($this->any())
            ->method('hasField')
            ->willReturnMap(
                [
                    ['name', true],
                ]
            );

        $this->metadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('name')
            ->willReturn('string');

        $result = $this->entityNameProvider->getName('short', null, $entity);
        $this->assertEquals(123, $result);
    }

    public function testGetNameFullEmptyNameButHasIdentifier()
    {
        $entity = new TestEntity(123);

        $this->metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->metadata->expects($this->any())
            ->method('hasField')
            ->willReturnMap(
                [
                    ['name', true],
                ]
            );

        $this->metadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('name')
            ->willReturn('string');

        $this->metadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['name']);

        $result = $this->entityNameProvider->getName('full', null, $entity);
        $this->assertEquals(123, $result);
    }

    public function testGetNameFullEmptyNameButNoIdentifier()
    {
        $entity = new TestEntity(123);

        $this->metadata->expects($this->any())
            ->method('hasField')
            ->willReturnMap(
                [
                    ['name', true],
                ]
            );

        $this->metadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('name')
            ->willReturn('string');

        $this->metadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['name']);

        $result = $this->entityNameProvider->getName('full', null, $entity);
        $this->assertFalse($result);
    }

    public function testGetNameDQLForUnsupportedFormat()
    {
        $result = $this->entityNameProvider->getNameDQL('test', null, self::ENTITY_CLASS, 'alias');
        $this->assertFalse($result);
    }

    public function testGetNameDQLShortNoIdentifier()
    {
        $this->metadata->expects($this->any())
            ->method('hasField')
            ->willReturnMap(
                [
                    ['name', true]
                ]
            );
        $this->metadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('name')
            ->willReturn('string');

        $result = $this->entityNameProvider->getNameDQL('short', null, self::ENTITY_CLASS, 'alias');
        $this->assertEquals('alias.name', $result);
    }

    public function testGetNameDQLShortWithIdentifier()
    {
        $this->metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->metadata->expects($this->any())
            ->method('hasField')
            ->willReturnMap(
                [
                    ['name', true],
                ]
            );
        $this->metadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('name')
            ->willReturn('string');

        $result = $this->entityNameProvider->getNameDQL('short', null, self::ENTITY_CLASS, 'alias');
        $this->assertEquals('COALESCE(alias.name, alias.id AS string)', $result);
    }

    public function testGetNameDQLForNotManageableEntity()
    {
        $result = $this->entityNameProvider->getNameDQL('short', null, 'Test\Class', 'alias');
        $this->assertFalse($result);
    }

    public function testGetNameDQLNoAppropriateField()
    {
        $result = $this->entityNameProvider->getNameDQL('short', null, self::ENTITY_CLASS, 'alias');
        $this->assertFalse($result);
    }

    public function testGetNameDQLShortNoAppropriateField()
    {
        $result = $this->entityNameProvider->getNameDQL('short', null, self::ENTITY_CLASS, 'alias');

        $this->assertFalse($result);
    }

    public function testGetNameDQLFullNoAppropriateFields()
    {
        $result = $this->entityNameProvider->getNameDQL('full', null, self::ENTITY_CLASS, 'alias');
        $this->assertFalse($result);
    }

    public function testGetNameDQLFull()
    {
        $this->metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->metadata->expects($this->any())
            ->method('hasField')
            ->willReturnMap(
                [
                    ['name', true],
                    ['description', true],
                ]
            );

        $this->metadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['name', 'description']);

        $this->metadata->expects($this->exactly(2))
            ->method('getTypeOfField')
            ->willReturn('string');

        $result = $this->entityNameProvider->getNameDQL('full', null, self::ENTITY_CLASS, 'alias');
        $this->assertEquals('COALESCE(CONCAT_WS(\' \', alias.name, alias.description), alias.id AS string)', $result);
    }

    public function testGetNameDQLFullNoIdentifier()
    {
        $this->metadata->expects($this->any())
            ->method('hasField')
            ->willReturnMap(
                [
                    ['name', true],
                    ['description', true],
                ]
            );

        $this->metadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['name', 'description']);

        $this->metadata->expects($this->exactly(2))
            ->method('getTypeOfField')
            ->willReturn('string');

        $result = $this->entityNameProvider->getNameDQL('full', null, self::ENTITY_CLASS, 'alias');
        $this->assertEquals('CONCAT_WS(\' \', alias.name, alias.description)', $result);
    }
}
