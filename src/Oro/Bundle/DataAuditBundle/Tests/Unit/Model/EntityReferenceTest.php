<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Model;

use Oro\Bundle\DataAuditBundle\Model\EntityReference;

class EntityReferenceTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldCreateNullReference()
    {
        new EntityReference();
    }

    public function testShouldNullReferenceBeInitializedAsLoaded()
    {
        $reference = new EntityReference();
        self::assertTrue($reference->isLoaded());
    }

    public function testShouldClassNameForNullReferenceBeNull()
    {
        $reference = new EntityReference();
        self::assertNull($reference->getClassName());
    }

    public function testShouldIdForNullReferenceBeNull()
    {
        $reference = new EntityReference();
        self::assertNull($reference->getId());
    }

    public function testShouldEntityForNullReferenceBeNull()
    {
        $reference = new EntityReference();
        self::assertNull($reference->getEntity());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage An entity cannot be set to "null" reference object.
     */
    public function testShouldNotBePossibleToSetEntityForNullReference()
    {
        $reference = new EntityReference();
        $reference->setEntity(new \stdClass());
    }

    public function testShouldCreateReference()
    {
        new EntityReference('Test\Class', 'testId');
    }

    public function testShouldReferenceBeInitializedAsNotLoaded()
    {
        $reference = new EntityReference('Test\Class', 'testId');
        self::assertFalse($reference->isLoaded());
    }

    public function testShouldClassNameBeInitialized()
    {
        $reference = new EntityReference('Test\Class', 'testId');
        self::assertEquals('Test\Class', $reference->getClassName());
    }

    public function testShouldIdBeInitialized()
    {
        $reference = new EntityReference('Test\Class', 'testId');
        self::assertEquals('testId', $reference->getId());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The entity is not loaded yet. Call "setEntity" method before.
     */
    public function testShouldThrowExceptionIfEntityIsNotLoadedYet()
    {
        $reference = new EntityReference('Test\Class', 'testId');
        $reference->getEntity();
    }

    public function testShouldBePossibleToSetEntity()
    {
        $entity = new \stdClass();
        $reference = new EntityReference(get_class($entity), 'testId');
        $reference->setEntity($entity);
        self::assertSame($entity, $reference->getEntity());
    }

    public function testShouldBePossibleToSetInheritedEntity()
    {
        $entityClass = get_class(new \stdClass());
        $entity = $this->getMockBuilder($entityClass)->getMock();

        // guard
        self::assertNotEquals($entityClass, get_class($entity));
        self::assertInstanceOf($entityClass, $entity);

        $reference = new EntityReference($entityClass, 'testId');
        $reference->setEntity($entity);
        self::assertSame($entity, $reference->getEntity());
    }

    public function testShouldBePossibleToSetNullEntity()
    {
        $reference = new EntityReference(get_class(new \stdClass()), 'testId');
        $reference->setEntity(null);
        self::assertNull($reference->getEntity());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected argument of type "null or instance of Test\Class", "integer" given.
     */
    public function testShouldSetEntityThrowExceptionIfNotObjectPassed()
    {
        $reference = new EntityReference('Test\Class', 'testId');
        $reference->setEntity(123);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected argument of type "null or instance of stdClass", "Oro\Bundle\DataAuditBundle\Model\EntityReference" given.
     */
    // @codingStandardsIgnoreEnd
    public function testShouldSetEntityThrowExceptionIfInvalidEntityTypePassed()
    {
        $reference = new EntityReference(get_class(new \stdClass()), 'testId');
        $reference->setEntity(new EntityReference());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The entity is already loaded.
     */
    public function testShouldNotBePossibleToChangeAlreadySetEntity()
    {
        $entity = new \stdClass();
        $reference = new EntityReference(get_class($entity), 'testId');
        $reference->setEntity($entity);
        // test
        $reference->setEntity($entity);
    }
}
